<?php

namespace LaraParse;

use Carbon\Carbon;

/*
 * Object Controller based on Parse Rest API documentation
 * https://parse.com/docs/rest/guide#quick-reference-objects
*/

/**
 * Class ParseObjectController
 * @package App\Http\Controllers\Parse
 */
class ParseObjectController extends ParseBaseController
{
    /**
     *
     */
    public function __construct()
    {
        try {
            ParseHelper::prepareRequest();
        } catch (\Exception $ex) {
            dd($ex->getMessage());
            return new ParseResponse($ex);
        }
    }

    /**
     * Creating Objects
     * https://parse.com/docs/rest/guide#objects-creating-objects
     *
     * @param $className
     * @return mixed
     */
    public function create($className)
    {
        $this->prepareClassMetadata($className);

        $inputs = $this->getInputs();

        $this->save($className,$inputs);

        return new ParseResponse($this->object);
    }

    /**
     * Retrieving Objects
     * https://parse.com/docs/rest/guide#objects-retrieving-objects
     *
     * @param $className
     * @param $objectId
     * @return mixed
     */
    public function getById($className, $objectId)
    {
        $this->prepareClassMetadata($className);

        $inputs['where'] = json_decode('{"'.$this->object->getKeyName().'":{"$eq":' . $objectId . '}}');
        try {
            $response = $this->query($className, $inputs, true);
            // return single object nested of full result
            if (isset($response['results'][0])) {
                return new ParseResponse($response['results'][0]);
            } else {
                ParseHelper::throwException(105);
            }
        } catch (\Exception $ex) {
            return new ParseResponse($ex);
        }
    }

    /**
     * Updating Objects
     * https://parse.com/docs/rest/guide#objects-updating-objects
     *
     * @param $className
     * @param $objectId
     */
    public function update($className, $objectId)
    {
        $this->prepareClassMetadata($className);

        $inputs = $this->getInputs();

        $this->save($className,$inputs,$objectId);

        return new ParseResponse($this->object);
    }

    /**
     * Queries
     * https://parse.com/docs/rest/guide#queries
     *
     * @param $className
     * @return mixed
     */
    public function get($className)
    {
        $this->prepareClassMetadata($className);

        $inputs = $this->getInputs();
        try {
            $response = $this->query($className, $inputs, true);

            return new ParseResponse($response);
        } catch (\Exception $ex) {
            return new ParseResponse($ex);
        }
    }

    /**
     * Deleting Objects
     * https://parse.com/docs/rest/guide#objects-deleting-objects
     *
     * @param $className
     * @param $objectId
     * @return mixed
     */
    public function delete($className, $objectId)  //delete all row from db
    {
        $this->prepareClassMetadata($className);

        $object = ParseHelper::makeModelObject($className);
        $find = $object->find($objectId);
        if (count($find) == 0) {
            return array("code" => 101, "error" => "object not found for delete");
        }
        $find->delete();

        return \Response::json(new \stdClass());
    }

    /**
     * Batch Operations
     * https://parse.com/docs/rest/guide#objects-batch-operations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function batch()
    {

        $requests = json_decode(file_get_contents('php://input'));
        $output = array();

        foreach ($requests->requests as $request) {
            $url = parse_url($request->path);
            $query = array();

            if (isset($url['query'])) {
                parse_str($url['query'], $query);
            }

            $body = (isset($request->body)) ? json_encode($request->body) : null;
            self::$currentRequest = \Request::create($request->path, $request->method, $query, array(), array(),
                array(), $body);

            $result = json_decode(\Route::dispatch(self::$currentRequest)->getContent());
            if (!property_exists($result, 'error')) {
                $output[] = array("success" => $result);
            } else {
                $output[] = array("error" => $result);
            }
        }

        return \Response::json($output);
    }
}