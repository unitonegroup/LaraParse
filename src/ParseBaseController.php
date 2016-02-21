<?php

namespace UnitOneICT\LaraParse;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Schema;

/**
 *  BaseParseController - class to execute the parse query and get values from DB models
 */
class ParseBaseController extends Controller
{
    /**
     * @var \Eloquent $object
     */
    protected $object;
    protected $columns;
    protected $relations;

    /**
     * used for batch request to handel each request specific inputs and data
     * @var \Request $currentRequest
     */
    protected static $currentRequest = null;

    /**
     * @param $className
     */
    protected function prepareClassMetadata($className)
    {
        $this->object = ParseHelper::makeModelObject($className);
        $this->columns = ParseHelper::getModelColumns($this->object);
        $this->relations = ParseHelper::getModelRelations($this->object);
    }

    /**
     * Get the inputs directly or from the batch request input
     * @return mixed
     */
    protected function getInputs()
    {
        if (!is_null(self::$currentRequest)) {
            return self::$currentRequest->json()->all();
        } else {
            return \Input::all();
        }
    }


    /**
     * @param $className
     * @param $parameters
     * @param bool $runQuery
     * @return array
     */
    public function query($className, $parameters, $runQuery = true)
    {
        // set the default parameters
        $defaultParameters = array(
            'where'   => null,
            'limit'   => null,
            'skip'    => null,
            'order'   => null,
            'keys'    => null,
            'count'   => null,
            'include' => null,
        );
        $parameters = array_merge($defaultParameters, $parameters);

        $where = $parameters['where'];
        $limit = $parameters['limit'];
        $skip = $parameters['skip'];
        $order = $parameters['order'];
        $keys = $parameters['keys'];
        $count = $parameters['count'];
        $include = $parameters['include'];

        // specify the selected columns
        $selected_columns = $this->select($keys);
        // eager loading functionality
        $this->with($include);
        // filtering the data
        // todo review this line
        $query = ($this->object instanceof Model) ? $this->object->query() : $this->object;
        $this->object = $this->where($query, $where);
        // support pagination using limit and skip (offset)
        $this->limit($limit, $skip);
        // order the result
        $this->order($order);

        if ($runQuery) {
            // prepare the json main structure
            $jsonData = array('results' => array());

            // run the query based on the above configuration
            $jsonData['results'] = $this->object->get($selected_columns);

            if (!is_null($count)) {
                // add the count index if the user ask for
                $jsonData['count'] = $this->object->count();
            }

            // return the parsed results and count if available
            return $jsonData;
        }
    }

    /**
     * @param $limit
     * @param $skip
     */
    public function limit($limit, $skip = null)
    {
        if (!is_null($limit) and is_numeric($limit)) {
            $this->object = $this->object->take($limit);
        } else {
            $this->object = $this->object->take(100);
        }

        if (!is_null($skip) and is_numeric($skip)) {
            $this->object = $this->object->skip($skip);
        }
    }

    /**
     * @param $order
     * @throws \Exception
     */
    public function order($order)
    {
        if (!is_null($order)) {
            $orders = explode(',', $order);
            foreach ($orders as $order) {
                // get the order key name without -
                $orderKey = trim($order, '-');
                // the password cannot be used for sorting
                if ($orderKey != 'password' && in_array($orderKey, $this->columns)) {
                    // check the order type ( - mean desc )
                    $orderType = ($order[0] == '-') ? 'desc' : 'asc';
                    $this->object = $this->object->orderby($orderKey, $orderType);
                } else {
                    ParseHelper::throwException(105);
                }
            }
        }
    }

    /**
     * @param $keys
     * @return array
     */
    public function select($keys)
    {
        if (!is_null($keys)) {
            // get the keys from the url
            $keys = explode(',', $keys);
            // validate that these keys are exists in database
            $keys = array_intersect($keys, $this->columns);
            // some fields must always returned back
            $keys = array_merge($keys, array(
                $this->object->getKeyName(),
                $this->object->getCreatedAtColumn(),
                $this->object->getUpdatedAtColumn(),
            ));

            // make sure we return unique values
            return array_unique($keys);
        }

        return array('*');
    }

    /**
     * @param $include
     */
    public function with($include)
    {
        // include key
        if (!is_null($include)) {
            $relations = explode(',', $include);
            foreach ($relations as $relation) {
                // todo validate the nested relations
                $directRelation = explode('.', $relation)[0];
                if (in_array($directRelation, $this->relations)) {
                    $this->object = $this->object->with($relation);
                } else {
                    ParseHelper::throwException(102);
                }
            }
        }
    }

    /**
     * @param $conditions
     * @param \Illuminate\Database\Eloquent\Builder $queryBuilder
     * @return mixed
     * @throws \Exception
     */
    public function where($queryBuilder, $conditions)
    {
        $conditions = is_object($conditions) ? $conditions : json_decode($conditions);
        // where always must be an object
        if (!is_null($conditions) && is_object($conditions)) {
            foreach ($conditions as $key => $keyConditions) {
                if ($key == '$or') {
                    // loop over each orQuery
                    foreach ($keyConditions as $orQueryConditions) {
                        // use orWhere laravel syntax
                        $queryBuilder = $queryBuilder->orWhere(function ($orWhereQuery) use ($orQueryConditions) {
                            // then loop again in each query
                            $this->where($orWhereQuery, $orQueryConditions);
                        });
                    }
                } else {
                    // simple query
                    $this->whereConditions($queryBuilder, $key, $keyConditions);
                }
            }
        }

        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     * @param $key
     * @param $conditions
     * @throws \Exception
     */
    private function whereConditions($queryBuilder, $key, $conditions)
    {
        $model = $queryBuilder->getModel();
        $modelColumns = ParseHelper::getModelColumns($model);
        $modelRelations = ParseHelper::getModelRelations($model);

        if (in_array($key, $modelColumns)) {
            $this->whereConditionTypes($queryBuilder, $key, $conditions);
        } elseif (in_array($key, $modelRelations)) {
            // if the the key is a relation, use whereHas laravel syntax
            $queryBuilder->whereHas($key, function ($whereHasQuery) use ($conditions) {
                $whereHasConditions = json_encode(array(
                    $whereHasQuery->getModel()->getKeyName() => $conditions
                ));
                $this->where($whereHasQuery, $whereHasConditions);
            });
        } else {
            ParseHelper::throwException(102, $key);
        }
    }

    /**
     * @param $queryBuilder
     * @param $key
     * @param $val
     * @param string $boolean
     * @return
     * @throws \Exception
     */
    public function whereConditionTypes($queryBuilder, $key, $val, $boolean = "and")
    {
        if (is_object($val) && !$val instanceof Carbon) {

            // simple operator mapping between parse and SQL
            $map = array(
                '$lt'  => '<',
                '$lte' => '<=',
                '$gt'  => '>',
                '$gte' => '>=',
                '$eq'  => '=',
                '$ne'  => '<>'
            );

            foreach ($map as $parseOperator => $operator) {
                if (property_exists($val, $parseOperator)) {
                    $tempVal = $val->{$parseOperator};
                    // the value can be direct value or object
                    if (is_object($tempVal) && isset($tempVal->{'__type'}) && $tempVal->{'__type'} == "Pointer") {
                        // todo not working perfectly
                        $pointer_class_name = $tempVal->{'className'};
                        $tempKey = strtolower($pointer_class_name . '_id');
                        $tempVal = $tempVal->{$this->object->getKeyName()};
                        $queryBuilder = $queryBuilder->where($tempKey, $operator, $tempVal, $boolean);
                    } elseif (property_exists($val, $parseOperator)) {
                        $queryBuilder = $queryBuilder->where($key, $operator, $tempVal, $boolean);
                    }
                }
            }

            //  Exist or not exist
            if (property_exists($val, '$exists')) {
                if ($val->{'$exists'} == true) {
                    $queryBuilder = $queryBuilder->whereNotNull($key, $boolean);
                } elseif ($val->{'$exists'} == false) {
                    $queryBuilder = $queryBuilder->whereNull($key, $boolean);
                } else {
                    ParseHelper::throwException(105);
                }
            }

            // containedIn (mysql -in-)
            if (property_exists($val, '$in')) {
                $pointers = $val->{'$in'};
                $ids = $this->getIds($pointers);
                $queryBuilder = $queryBuilder->whereIn($key, $ids, $boolean);
            }

            // notContainedIn (mysql -not in-)
            if (property_exists($val, '$nin')) {
                $pointers = $val->{'$nin'};
                $ids = $this->getIds($pointers);
                $queryBuilder = $queryBuilder->whereNotIn($key, $ids, $boolean);
            }

            //MatchKeyInQuery
            if (property_exists($val, '$select')) {
                $innerQuery = $val->{'$select'}->{'query'};
                $innerQueryKey = $val->{'$select'}->{'key'};
                $innerQueryClassName = $innerQuery->{'className'};

                $ids = $this->getInnerQueryIds($innerQueryClassName, $innerQuery, $innerQueryKey);
                $queryBuilder = $queryBuilder->whereIn($key, $ids, $boolean);
            }

            //DoesNotMatchKeyInQuery
            if (property_exists($val, '$dontSelect')) {
                $innerQuery = $val->{'$dontSelect'}->{'query'};
                $innerQueryKey = $val->{'$dontSelect'}->{'key'};
                $innerQueryClassName = $innerQuery->{'className'};

                $ids = $this->getInnerQueryIds($innerQueryClassName, $innerQuery, $innerQueryKey);
                $queryBuilder = $queryBuilder->whereNotIn($key, $ids, $boolean);
            }

            // match in query
            if (property_exists($val, '$inQuery')) {
                $innerQuery = $val->{'$inQuery'};
                $innerQueryKey = $this->object->getKeyName();
                $innerQueryClassName = $innerQuery->{'className'};

                $ids = $this->getInnerQueryIds($innerQueryClassName, $innerQuery, $innerQueryKey);
                $queryBuilder = $queryBuilder->whereIn($key, $ids, $boolean);
            }


            // doesn't Match in Query
            if (property_exists($val, '$notInQuery')) {
                $innerQuery = $val->{'$notInQuery'};          //get the inner query
                $innerQueryKey = $this->object->getKeyName();
                $innerQueryClassName = $innerQuery->{'className'};

                $ids = $this->getInnerQueryIds($innerQueryClassName, $innerQuery, $innerQueryKey);
                $queryBuilder = $queryBuilder->whereNotIn($key, $ids, $boolean);
            }

            // regular expression
            if (property_exists($val, '$regex')) {
                //remove the regular syntax
                $string_without_regex = substr(substr($val->{'$regex'}, 0, -2), 3);
                //remove the space in first and last string
                $pure_string = htmlspecialchars(trim($string_without_regex));
                $queryBuilder = $queryBuilder->where($key, "LIKE", $pure_string . "%", $boolean);
            }

            // pointer
            if (property_exists($val, '__type')) {
                if ($val->{'__type'} == "Pointer") {
                    $objectId = $val->{'objectId'};
                    $pointer_class_name = $val->{'className'};
                    // todo not perfect, same as equal to
                    $queryBuilder = $queryBuilder->where(strtolower($pointer_class_name . '_id'), '=', $objectId,
                        $boolean);
                }
            }
        } elseif ($key == '$or') {
            // todo !!!!
        } else {
            $queryBuilder = $queryBuilder->where($key, '=', $val, $boolean);
        }

        return $queryBuilder;
    }

    /**
     * Get the ids of Parse Pointers
     * @param $pointers
     * @return array
     */
    public function getIds($pointers)
    {
        // todo can we delete the pointer at the Request Prepare?
        $ids = array();
        if (is_array($pointers)) {
            foreach ($pointers as $object) {
                if (is_numeric($object)) {
                    $ids[] = $object;
                } elseif (is_object($object) && $object->__type == 'Pointer') {
                    $ids[] = $object->{$this->object->getKeyName()};
                }
            }

            return $ids;
        }

        return $ids;
    }

    /**
     * @param $innerQueryClassName
     * @param $innerQuery
     * @param $innerQueryKey
     * @return array
     */
    public function getInnerQueryIds($innerQueryClassName, $innerQuery, $innerQueryKey = "id")
    {
        $innerQueryParameters = $this->buildInnerQueryParameters($innerQuery);

        $bpc = new ParseBaseController();
        // prepare the query builder
        $bpc->query($innerQueryClassName, $innerQueryParameters, false);

        // get the list of required key
        return $bpc->object->lists($innerQueryKey);
    }

    /**
     * @param $innerQuery
     * @return mixed
     */
    public function buildInnerQueryParameters($innerQuery)
    {
        $innerQueryParameters = array();

        foreach ($innerQuery as $key => $value) {
            $innerQueryParameters[$key] = $value;
        }

        return $innerQueryParameters;
    }

    /**
     * @param $className
     * @param $parameters
     * @param null $objectId
     * @return ParseResponse
     * @throws \Exception
     */
    public function save($className, $parameters, $objectId = null)
    {
        $values = array();

        // if update, try to get the current object at first
        if ($objectId) {
            $this->object = $this->object->findOrFail($objectId);
        }

        foreach ($parameters as $key => $val) {
            if (in_array($key, $this->columns)) {
                if (is_array($val) && $operation = array_get($val, '__op')) {
                    switch ($operation) {
                        case "Increment":
                            $val = ($this->object->$key) ? $this->object->$key + $val['amount'] : $val['amount'];
                            break;
                        case "decrement":
                            $val = $this->object->$key - $val['amount'];
                            break;
                        case "Delete":
                            $val = null;
                            break;
                        case "Batch":
//                            $ops = $val['ops'];
//                            foreach ($ops as $ops_key => $ops_val) {
//                                if ($ops_val['__op'] == 'AddRelation') {
//                                    foreach ($ops_val['objects'] as $objects_key => $objects_val) {
//                                        $relation_type = $objects_val['__type'];
//                                        $relation_class_name = $objects_val['className'];
//                                        $relation_class_name = ParseHelperClass::removeUnderscoreFromClassName($relation_class_name);     // to remove '_' character if founded in the name of class _User
//                                        $relation_objectId = $objects_val['objectId'];
//                                        $relation_object = ParseHelperClass::createObject($relation_class_name);
//                                        $updatedAt = '';
//
//                                        if (!$relation_object) {
//                                            return ParseHelperClass::error_message_return('105');
//                                        }
//
//                                        $object->find($objectId)->$key()->sync([$relation_objectId], false);
//
//                                        $relation_data_of_object = $object::find(60)->$key()->where($relation_class_name . '_id',
//                                            '=', $relation_objectId)->get();
//
//                                        foreach ($relation_data_of_object as $relation_data) {
//                                            $updatedAt = $relation_data->pivot->createdAt;
//                                        }
//                                    }
//
//                                    return array(
//                                        "updatedAt" => Carbon::parse($updatedAt)->format('Y-m-d\TH:i:s.z\Z')
//                                    );
//                                }
//                            }
                            break;
                    }
                }
                $values[$key] = $val;
            }
            elseif (in_array($key, $this->relations)) {
                //
            } else {
                if ($key != "ACL") {
                    ParseHelper::throwException('210', $key);
                }
            }
        }

        $this->object->fill($values);
        try {
            $event = ($this->object->getKey()) ? 'update' : 'insert';
            \Event::fire("before.$event." . $this->object->getTable(), $this->object);
            $this->object->save();  //create new object
            // now we can attach the relations
            foreach ($parameters as $key => $val) {
                if (in_array($key, $this->relations)) {
                    if ($operation = array_get($val, '__op')) {
                        switch ($operation) {
                            case "Delete":
                                $this->object->$key()->delete($val);
                                break;
                        }
                    }elseif ($this->object->$key() instanceof BelongsTo) {
                        $this->object->$key()->associate($val);
                    } elseif (!($this->object->$key() instanceof BelongsTo)) {
                        $this->object->$key()->save($val);
                    }else{
                        ParseHelper::throwException(105);
                    }
                }
            }
            \Event::fire("after.$event." . $this->object->getTable(), $this->object);

        } catch (\Exception $ex) {
            ParseHelper::throwException(141, $ex->getMessage());
        }
    }
}