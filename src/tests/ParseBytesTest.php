<?php

use Parse\ParseBytes;
use Parse\ParseObject;
use Parse\ParseQuery;

require_once 'ParseTestHelper.php';

class ParseBytesTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        ParseTestHelper::setUp();
    }

    public function setUp()
    {
        ParseTestHelper::clearClass("TestObject");
    }

    public function tearDown()
    {
//        ParseTestHelper::clearClass("TestObject");
        ParseTestHelper::tearDown();
    }

    public function dtestParseBytesFromArray()
    {
        $obj = ParseObject::create("TestObject");
        $bytes = ParseBytes::createFromByteArray([70, 111, 115, 99, 111]);
        $obj->set("byteColumn", $bytes);
        $obj->save();

        $query = new ParseQuery("TestObject");
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals("Fosco", $objAgain->get("byteColumn"));
    }

    public function dtestParseBytesFromBase64Data()
    {
        $obj = ParseObject::create("TestObject");
        $bytes = ParseBytes::createFromBase64Data("R3JhbnRsYW5k");
        $obj->set("byteColumn", $bytes);
        $obj->save();

        $query = new ParseQuery("TestObject");
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals("Grantland", $objAgain->get("byteColumn"));
    }
}
