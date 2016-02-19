<?php

use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;

class ParseTestHelper
{
    public static function setUp()
    {
        $app_id     = "8PYzxLlTqztkDEBCx3oDH6m6CqXYRpb4QTCWuuMw";
        $rest_key   = "VUEHy9GWuE9SJdCn8wvScoWAMYWun6PbpzqP8KAh";
        $master_key = "WOyCyOO7gXaMs2wCqgwmBsWDxo8R7CIPhDH9baOM";

        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        date_default_timezone_set('UTC');
        ParseClient::initialize(
            $app_id,
            $rest_key,
            $master_key
        );
    }

    public static function tearDown()
    {
    }

    public static function clearClass($class)
    {
        $query = new ParseQuery($class);
        $query->each(
            function (ParseObject $obj) {
                $obj->destroy(true);
            }, true
        );
    }
}
