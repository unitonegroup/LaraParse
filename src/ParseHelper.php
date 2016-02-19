<?php

namespace LaraParse;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use ReflectionClass;


class ParseHelper
{

    public static function throwException($error_code = "", $additionalInfo = "")
    {
        $message = "";
        switch ($error_code) {
            case 101 :
                $message = "invalid login parameter, or $additionalInfo";
                break;
            case 111:
                $message = "invalid type for key $additionalInfo, expected string, but got number";
                break;
            case 102:
                $message = "Invalid key type for find $additionalInfo";
                break;
            case 105:
                $message = "request is not valid";
                break;
            case 125:
                $message = "invalid email address";
                break;
            case 202:
                $message = "bad characters in classname"; //"class $added_to_message is not found";
                break;
            case 205:
                $message = "no user found with email $additionalInfo";
                break;
            case 210:
                $message = "invalid field name $additionalInfo";
                break;
            case 209:
                $message = "invalid session token";
                break;
            case 1000:
                $message = "$additionalInfo is not found";
                break;
            case 1001:
                $message = "parent class $additionalInfo is not found";
                break;
            case 1002:
                $message = "parent objectId not found";
                break;
            default:
                $error_code = 141;
                $message = $additionalInfo;
        }
        throw new \Exception($message, $error_code);
    }

    public static function makeModelObject($classNameOrObject)
    {
        // if we send an object, just return it
        if (is_object($classNameOrObject)) {
            return $classNameOrObject;
        }

        // be sure that the class name is Upper case
        $classNameOrObject = ucfirst($classNameOrObject);

        // get list of Models paths both from CMS or enabled Addons
        $paths = self::getModelsPaths();

        // loop over the paths and try to get the model
        foreach ($paths as $path) {
            $fullClassName = $path . $classNameOrObject;
            if (class_exists($fullClassName)) {
                return \App::make($fullClassName);
            }
        }

        // if class not found, thow an exception
        self::throwException(105);
    }

    public static function prepareRequest()
    {
        $className = \Request::segment(3);

        if (isset($className)) {

            $object = self::makeModelObject($className);

            $inputs = Input::all();
            $inputs = self::prepareRequestBasicFields($inputs, $object);
            $inputs = self::prepareRequestDateFields($inputs);
            $inputs = self::prepareRequestBytesFields($inputs);
            $inputs = self::prepareRequestRelations($inputs, $object);
            Input::replace($inputs);
        }
    }

    public static function prepareRequestBasicFields($inputs, $object)
    {
        $map = array(
            'objectId'  => $object->getKeyName(),
            'createdAt' => $object->getCreatedAtColumn(),
            'updatedAt' => $object->getUpdatedAtColumn(),
        );

        // replace the map key with the corresponding values
        $inputs = json_encode($inputs);
        $inputs = str_replace(array_keys($map), $map, $inputs);
        $inputs = json_decode($inputs, true);

        return $inputs;
    }

    public static function prepareRequestDateFields($inputs)
    {
        foreach ($inputs as $key => $val) {
            if ($key == "createdAt" || $key == "updatedAt") {
                $inputs[$key] = new Carbon($val);
            } elseif (is_array($val)) {
                if (array_get($val,'__type') == 'Date') {
                    $inputs[$key] = new Carbon($val['iso']);
                } else {
                    // recursive for Parse Objects
                    $inputs[$key] = SELF::prepareRequestDateFields($val);
                }
            }
        }

        return $inputs;
    }

    private static function prepareRequestBytesFields($inputs)
    {
        foreach ($inputs as $key => $val) {
            if (is_array($val)) {
                if (array_get($val,'__type') == 'Bytes') {
                    $inputs[$key] = $val['base64'];
                } else {
                    // recursive for Parse Objects
                    $inputs[$key] = SELF::prepareRequestBytesFields($val);
                }
            }
        }

        return $inputs;
    }

    private static function prepareRequestRelations($inputs)
    {
        foreach ($inputs as $key => $val) {
            if (is_array($val)) {
                if (array_get($val,'__type') == 'Pointer') {
                    // convert Pointers to Eloquent Models
                    $inputs[$key] = self::makeModelObject($val['className'])->find($val['objectId']);
                } else {
                    // recursive for Parse Objects
                    $inputs[$key] = SELF::prepareRequestRelations($val);
                }
            }
        }

        return $inputs;
    }

    public static function getModelColumns($classNameOrObject)
    {
        $object = self::makeModelObject($classNameOrObject);

        // todo: use database of parseFillable?
        return \Schema::getColumnListing($object->getTable());
    }

    public static function getModelRelations($classNameOrObject)
    {
        $relations = array();
        $object = self::makeModelObject($classNameOrObject);
        $methods = get_class_methods($object);

        if ($methods) {
            foreach ($methods as $method) {
                // loop over the method and try to detect the relation methods
                $reflection = new \ReflectionMethod($object, $method);
                $file = new \SplFileObject($reflection->getFileName());
                $file->seek($reflection->getStartLine() - 1);
                $code = '';
                while ($file->key() < $reflection->getEndLine()) {
                    $code .= $file->current();
                    $file->next();
                }
                $begin = strpos($code, 'function(');
                $code = substr($code, $begin, strrpos($code, '}') - $begin + 1);

                $supportedRelations = array(
                    'hasMany',
                    'belongsToMany',
                    'hasOne',
                    'belongsTo',
                    'morphTo',
                    'morphMany',
                    'morphToMany'
                );
                foreach ($supportedRelations as $relation) {
                    $search = '$this->' . $relation . '(';
                    if ($pos = stripos($code, $search)) {
                        // currently we just need the relation name
                        // todo return additional info like Model and Foreign key
                        if ($method != "morphedByMany") {
                            $relations[] = $method;
                        }
                    }
                }
            }
        }

        return $relations;
    }

    /**
     * @return array
     */
    public static function getModelsPaths()
    {
        // todo this function must be overidable to remove the dependency with the SN
        $paths = array();
        $paths[] = 'App\\ParseModel\\';
        return $paths;
    }

    public static function prepareResponse($response)
    {
        $results = (isset($response['results'])) ? $response['results'] : $response;

        self::prepareResponseBasicFields($results);
        self::prepareResponseDateFields($results);
        self::prepareResponseBytesFields($results);
        self::prepareResponseRelations($results);
        $results = $results->toArray();

        if (isset($response['results'])) {
            $response['results'] = $results;
        } else {
            $response = $results;
        }

        return $response;
    }

    private static function prepareResponseBasicFields($results)
    {
        if ($results instanceof Collection) {
            foreach ($results as $object) {
                self::prepareResponseBasicFields($object);
            }
        } else {
            $map = array(
                'objectId'  => $results->getKeyName(),
                'createdAt' => $results->getCreatedAtColumn(),
                'updatedAt' => $results->getUpdatedAtColumn(),
            );

            foreach ($map as $parseKey => $key) {
                if ($parseKey != $key) {
                    $results->$parseKey = $results->$key;
                    unset($results->$key);
                }
            }
        }
    }

    private static function prepareResponseDateFields($results)
    {
        if ($results instanceof Collection) {
            foreach ($results as $object) {
                self::prepareResponseDateFields($object);
            }
        } else {
            foreach ($results->getAttributes() as $key => $val) {

                if ($val === "0000-00-00 00:00:00") {
                    $results->$key = null;
                } else {
                    if ($key == "createdAt" || $key == "updatedAt") {
                        // todo conflict if the database name = to parse name
                        try {
                            $results->$key = (string)$results->$key;
                        } catch (\Exception $ex) {
                        }
                    } elseif (is_string($val) && is_valid_datetime($val)) {
                        $results->$key = array(
                            "__type" => "Date",
                            "iso"    => self::parseDate($val)
                        );
                    }
                }
            }
        }
    }

    private static function prepareResponseBytesFields($results)
    {
        if ($results instanceof Collection) {
            foreach ($results as $object) {
                self::prepareResponseBytesFields($object);
            }
        } else {
            $bytesColumns = $results->bytesColumns ? $results->bytesColumns : array();
            foreach ($bytesColumns as $column) {
                $results->$column = array(
                    "__type" => "Bytes",
                    "base64" => $results->$column
                );
            }
        }
    }

    private static function prepareResponseRelations($results)
    {
        if ($results instanceof Collection) {
            foreach ($results as $object) {
                self::prepareResponseRelations($object);
            }
        } else {
            $columns = self::getModelColumns($results);
            $relations = self::getModelRelations($results);
            // eager loaded relations with its data
            $includedRelations = $results->getRelations();
            // relations that are not eager loaded
            $notIncludedRelations = array_diff($relations, array_keys($includedRelations));

            // convert $notIncludedRelations to parse Pointers
            foreach ($notIncludedRelations as $relation) {
                // todo move it to ModelRelations function (model and fk)
                $fk = $relation . "_id";
                $reflect = new ReflectionClass($results->$relation()->getQuery()->getModel());
                $className = $reflect->getShortName();

                if (in_array($fk, $columns)) {
                    $results->$relation = null;
                    if ($results->$fk) {
                        $results->$relation = new \stdClass();
                        $results->$relation->__type = 'Pointer';
                        $results->$relation->className = $className;
                        $results->$relation->objectId = $results->$fk;
                    }
                    unset($results->$fk);
                }
            }

            // convert $includedRelations to parse Objects
            // and prepare its child to support deep include (e.g. post.likes.user)
            foreach ($includedRelations as $relationName => $relationData) {
                if (!is_null($relationData)) {
                    if ($relationData instanceof Collection) {
                        foreach ($relationData as $relationObject) {
                            self::prepareResponseIncludedRelations($relationObject);
                        }
                    } else {
                        self::prepareResponseIncludedRelations($relationData);
                    }
                }
                // todo remove the fk_id if relation is included
            }
        }
    }

    /**
     * @param $val
     * @return string
     */
    private static function parseDate($val)
    {
        return Carbon::parse($val)->format('Y-m-d\\TH:i:s.z\Z');
    }

    /**
     * @param $relationData
     * @return array
     */
    private static function prepareResponseIncludedRelations($relationData)
    {
        $reflect = new ReflectionClass($relationData);
        $className = $reflect->getShortName();

        $relationData->__type = 'Object';
        $relationData->className = $className;
        // allow for deep response preparation
        self::prepareResponse($relationData);
    }
}

function is_valid_datetime($dateTime)
{
    if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
        if (checkdate($matches[2], $matches[3], $matches[1])) {
            return true;
        }
    }

    return false;
}
