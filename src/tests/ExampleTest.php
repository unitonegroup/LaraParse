<?php

use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;

class ExampleTest extends TestCase
{
    public $objects;

    public function testObject()
    {
        $app_id     = "8PYzxLlTqztkDEBCx3oDH6m6CqXYRpb4QTCWuuMw";
        $rest_key   = "VUEHy9GWuE9SJdCn8wvScoWAMYWun6PbpzqP8KAh";
        $master_key = "WOyCyOO7gXaMs2wCqgwmBsWDxo8R7CIPhDH9baOM";

        ParseClient::$HOST_NAME = "http://localhost/parse/public/";
        ParseClient::initialize( $app_id, $rest_key, $master_key );

        $response = $this->createObject();
        $this->assertArrayHasKey( "objectId", $response );
        $this->assertArrayHasKey( "createdAt", $response );

        $objectId = $response['objectId'];

        $response = $this->retrieveObject( $objectId );
        $this->assertEquals( 1, count( $response['results'] ) );
        $this->assertArrayHasKey( "objectId", $response['results'][0] );
        $this->assertArrayHasKey( "createdAt", $response['results'][0] );

        $response = $this->updateObject( $objectId );
        $this->assertArrayHasKey( "objectId", $response );
        $this->assertArrayHasKey( "updatedAt", $response );

        $response = $this->incrementObject( $objectId );
//        $this->assertEquals( $response['testint'], 2 ,json_encode($response));

        $response = $this->deleteField( $objectId );
        $this->assertArrayHasKey( "updatedAt", $response );

        // try to delete the created object
        $response = $this->destroyObject( $objectId );
        $this->assertEquals( "{}", $response );

        // try to delete the object again
        $response = $this->destroyObject( $objectId );
        $this->assertEquals( "101", $response['code'] );
        $this->assertEquals( "object not found for delete", $response['error'] );
    }

    public function testQuery()
    {
        $app_id     = "XASVlMZE1IVe3zCDpHT5mzhTiEd3su7IWM68Uepa";
        $rest_key   = "zBwmJIkcOE1dvX3GUIpJT5ebLOhhPhn1NmsdWC6e";
        $master_key = "0zNVL0uJnxUiU9r1Ogvii7vLW8tOASIyS5j5HK4G";

        ParseClient::$HOST_NAME = "http://localhost/parse/public/";
        ParseClient::initialize( $app_id, $rest_key, $master_key );

        \App\Test::truncate();

        $response = $this->generalQuery();
        $this->assertArrayHasKey("results",$response);
        $this->assertEmpty($response["results"]);

        $this->objects = factory(\App\Test::class,50)->create();

        $response = $this->generalQuery();
        $this->assertArrayHasKey("results",$response);
        $this->assertEquals(50,count($response["results"]));

        $response = $this->firstQuery();
        $this->assertArrayHasKey("results",$response);
        $this->assertEquals(1,count($response["results"]));

        $response = $this->equalToQuery();
        $this->assertArrayHasKey("results",$response);
        $this->assertEquals(1,count($response["results"]));

        $response = $this->notEqualToQuery();
        $this->assertArrayHasKey("results",$response);
        $this->assertEquals(49,count($response["results"]));

        $response = $this->limitQuery();
        $this->assertArrayHasKey("results",$response);
        $this->assertEquals(10,count($response["results"]));
    }

    /**
     * @return mixed
     */
    public function createObject()
    {
        $object = new ParseObject( "Test" );
        $object->set( "name", "test" );
        $object->set( "testint", 1 );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $object->save();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    /**
     * @param $objectId
     *
     * @return mixed|string
     */
    public function destroyObject( $objectId )
    {
        $object = new ParseObject( "Test", $objectId );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $object->destroy();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function updateObject( $objectId )
    {
        $object = new ParseObject( "Test", $objectId );
        $object->set( "name", "test1" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $object->save();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function incrementObject( $objectId )
    {
        $object = new ParseObject( "Test", $objectId );
        $object->increment( "testint" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $object->save();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function deleteField( $objectId )
    {
        $object = new ParseObject( "Test", $objectId );
        $object->delete( "name" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $object->save();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function retrieveObject( $objectId )
    {
        $query = new ParseQuery( "Test" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $object                      = $query->get( $objectId );
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    /**
     * @return mixed|string
     */
    public function generalQuery()
    {
        $query = new ParseQuery( "Test" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $results = $query->find();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function firstQuery()
    {
        $query = new ParseQuery( "Test" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $results = $query->first();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function notEqualToQuery()
    {
        $query = new ParseQuery( "Test" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $query->notEqualTo("name",$this->objects->first()->name);
            $results = $query->find();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function equalToQuery()
    {
        $query = new ParseQuery( "Test" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $query->equalTo("name",$this->objects->first()->name);
            $results = $query->find();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }

    public function limitQuery()
    {
        $query = new ParseQuery( "Test" );
        try {
            ParseClient::$THROW_RESPONSE = true;
            $query->limit(10);
            $results = $query->find();
        } catch ( \Exception $ex ) {
            $msg = json_decode( $ex->getMessage(), true );

            return ( $msg ) ? $msg : $ex->getMessage();
        }
    }
}
