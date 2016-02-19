<?php

use Parse\ParseACL;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;

require_once 'ParseTestHelper.php';

/**
 * Class ParseQueryTest
 */
class ParseQueryTest extends PHPUnit_Framework_TestCase
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
        ParseTestHelper::tearDown();
    }

    /**
     * This function used as a helper function in test functions to save objects.
     *
     * @param int      $numberOfObjects Number of objects you want to save.
     * @param callable $callback        Function which takes int as a parameter.
     *                                  and should return ParseObject.
     */
    public function saveObjects($numberOfObjects, $callback)
    {
        $allObjects = [];
        for ($i = 0; $i < $numberOfObjects; $i++) {
            $object = $callback($i);
            $allObjects[] =$object;
        }
        ParseObject::saveAll($allObjects);
    }

    public function provideTestObjects($numberOfObjects)
    {
        $this->saveObjects(
            $numberOfObjects, function ($i) {
                $obj = ParseObject::create('TestObject');
                $obj->set('foo', 'bar'.$i);

                return $obj;
            }
        );
    }

    public function dtestBasicQuery()
    {
        $baz = new ParseObject("TestObject");
        $baz->set("foo", "baz");
        $qux = new ParseObject("TestObject");
        $qux->set("foo", "qux");
        $baz->save();
        $qux->save();

        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "baz");
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not find object.'
        );
        $this->assertEquals(
            "baz", $results[0]->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function dtestQueryWithLimit()
    {
        $baz = new ParseObject("TestObject");
        $baz->set("foo", "baz");
        $qux = new ParseObject("TestObject");
        $qux->set("foo", "qux");
        $baz->save();
        $qux->save();

        $query = new ParseQuery("TestObject");
        $query->limit(1);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestEqualTo()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $results = $query->find();
        $this->assertTrue(count($results) == 1, 'Did not find object.');
    }

    public function dtestNotEqualTo()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->notEqualTo('foo', 'bar9');
        $results = $query->find();
        $this->assertEquals(
            count($results), 9,
            'Did not find 9 objects, found '.count($results)
        );
    }

    public function dtestLessThan()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThan('foo', 'bar1');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'LessThan function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar0',
            'LessThan function did not return the correct object'
        );
    }

    public function dtestLessThanOrEqualTo()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualTo('foo', 'bar0');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'LessThanOrEqualTo function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar0',
            'LessThanOrEqualTo function did not return the correct object.'
        );
    }

    public function dtestStartsWithSingle()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'bar0');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar0',
            'StartsWith function did not return the correct object.'
        );
    }

    public function dtestStartsWithMultiple()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 10,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function dtestStartsWithMiddle()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'ar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function nytestStartsWithRegexDelimiters()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foob\E");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foob\E');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobE');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function dtestStartsWithRegexDot()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foobarfoo");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foo(.)*');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo.*');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function dtestStartsWithRegexSlash()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foobarfoo");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foo/bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function dtestStartsWithRegexQuestionmark()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foobarfoo");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foox?bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo(x)?bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function dtestGreaterThan()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->greaterThan('foo', 'bar8');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'GreaterThan function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar9',
            'GreaterThan function did not return the correct object.'
        );
    }

    public function dtestGreaterThanOrEqualTo()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualTo('foo', 'bar9');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'GreaterThanOrEqualTo function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar9',
            'GreaterThanOrEqualTo function did not return the correct object.'
        );
    }

    public function dtestLessThanOrEqualGreaterThanOrEqual()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualTo('foo', 'bar4');
        $query->greaterThanOrEqualTo('foo', 'bar2');
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'LessThanGreaterThan did not return correct number of objects.'
        );
    }

    public function dtestLessThanGreaterThan()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThan('foo', 'bar5');
        $query->greaterThan('foo', 'bar3');
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'LessThanGreaterThan did not return correct number of objects.'
        );
        $this->assertEquals(
            'bar4', $results[0]->get('foo'),
            'LessThanGreaterThan did not return the correct object.'
        );
    }

    public function dtestObjectIdEqualTo()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $boxedNumberArray = [];
        $this->saveObjects(
            5, function ($i) use (&$boxedNumberArray) {
                $boxedNumber = new ParseObject("BoxedNumber");
                $boxedNumber->set("number", $i);
                $boxedNumberArray[] = $boxedNumber;

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->equalTo("objectId", $boxedNumberArray[4]->getObjectId());
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not find object.'
        );
        $this->assertEquals(
            4, $results[0]->get("number"),
            'Did not return the correct object.'
        );
    }

    public function dtestFindNoElements()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            5, function ($i) {
                $boxedNumber = new ParseObject("BoxedNumber");
                $boxedNumber->set("number", $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->equalTo("number", 17);
        $results = $query->find();
        $this->assertEquals(
            0, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestFindWithError()
    {
        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'Invalid key', 102);
        $query->equalTo('$foo', 'bar');
        $query->find();
    }

    public function dtestGet()
    {
        $testObj = ParseObject::create("TestObject");
        $testObj->set("foo", "bar");
        $testObj->save();
        $query = new ParseQuery("TestObject");
        $result = $query->get($testObj->getObjectId());
        $this->assertEquals(
            $testObj->getObjectId(), $result->getObjectId(),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            "bar", $result->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function dtestGetError()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'Object not found', 101);
        $query->get("InvalidObjectID");
    }

    public function dtestGetNull()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'Object not found', 101);
        $query->get(null);
    }

    public function dtestFirst()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "bar");
        $testObject->save();
        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "bar");
        $result = $query->first();
        $this->assertEquals(
            "bar", $result->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function dtestFirstWithError()
    {
        $query = new ParseQuery("TestObject");
        $query->equalTo('$foo', 'bar');
        $this->setExpectedException('Parse\ParseException', 'Invalid key', 102);
        $query->first();
    }

    public function dtestFirstNoResult()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "bar");
        $testObject->save();
        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "baz");
        $result = $query->first();
        $this->assertTrue(
            empty($result),
            'Did not return correct number of objects.'
        );
    }

    public function dtestFirstWithTwoResults()
    {
        $this->saveObjects(
            2, function ($i) {
                $testObject = ParseObject::create("TestObject");
                $testObject->set("foo", "bar");

                return $testObject;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "bar");
        $result = $query->first();
        $this->assertEquals(
            "bar", $result->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function dtestNotEqualToObject()
    {
        ParseTestHelper::clearClass("Container");
        ParseTestHelper::clearClass("Item");
        $items = [];
        $this->saveObjects(
            2, function ($i) use (&$items) {
                $items[] = ParseObject::create("Item");

                return $items[$i];
            }
        );
        $this->saveObjects(
            2, function ($i) use ($items) {
                $container = ParseObject::create("Container");
                $container->set("item", $items[$i]);

                return $container;
            }
        );
        $query = new ParseQuery("Container");
        $query->notEqualTo("item", $items[0]);
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return the correct object.'
        );
    }

    public function dtestSkip()
    {
        $this->saveObjects(
            2, function ($i) {
                return ParseObject::create("TestObject");
            }
        );
        $query = new ParseQuery("TestObject");
        $query->skip(1);
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return the correct object.'
        );
        $query->skip(3);
        $result = $query->find();
        $this->assertEquals(
            0, count($result),
            'Did not return the correct object.'
        );
    }

    public function dtestSkipDoesNotAffectCount()
    {
        $this->saveObjects(
            2, function ($i) {
                return ParseObject::create("TestObject");
            }
        );
        $query = new ParseQuery("TestObject");
        $count = $query->count();
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
        $query->skip(1);
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
        $query->skip(3);
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
    }

    public function dtestCount()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            3, function ($i) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("x", $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->greaterThan("x", 1);
        $count = $query->count();
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
    }

    public function dtestCountError()
    {
        $query = new ParseQuery("Test");
        $query->equalTo('$foo', "bar");
        $this->setExpectedException('Parse\ParseException', 'Invalid key', 102);
        $query->count();
    }

    public function dtestOrderByAscendingNumber()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $numbers = [3, 1, 2];
        $this->saveObjects(
            3, function ($i) use ($numbers) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $numbers[$i]);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->ascending("number");
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $i + 1, $results[$i]->get("number"),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestOrderByDescendingNumber()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $numbers = [3, 1, 2];
        $this->saveObjects(
            3, function ($i) use ($numbers) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $numbers[$i]);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->descending("number");
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                3 - $i, $results[$i]->get("number"),
                'Did not return the correct object.'
            );
        }
    }

    public function provideTestObjectsForQuery($numberOfObjects)
    {
        $this->saveObjects(
            $numberOfObjects, function ($i) {
                $parent = ParseObject::create("PParent");
                $child = ParseObject::create("Child");
                $child->set("x", $i);
                $parent->set("x", 10 + $i);
                $parent->set("child", $child);

                return $parent;
            }
        );
    }

    public function dtestMatchesQuery()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $this->provideTestObjectsForQuery(10);
        $subQuery = new ParseQuery("Child");
        $subQuery->greaterThan("x", 5);
        $query = new ParseQuery("PParent");
        $query->matchesQuery("child", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        foreach ($results as $parentObj) {
            $this->assertGreaterThan(
                15, $parentObj->get("x"),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestDoesNotMatchQuery()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $this->provideTestObjectsForQuery(10);
        $subQuery = new ParseQuery("Child");
        $subQuery->greaterThan("x", 5);
        $query = new ParseQuery("PParent");
        $query->doesNotMatchQuery("child", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            6, count($results),
            'Did not return the correct object.'
        );
        foreach ($results as $parentObj) {
            $this->assertLessThanOrEqual(
                15, $parentObj->get("x"),
                'Did not return the correct object.'
            );
            $this->assertGreaterThanOrEqual(
                10, $parentObj->get("x"),
                'Did not return the correct object.'
            );
        }
    }

    public function provideTestObjectsForKeyInQuery()
    {
        ParseTestHelper::clearClass("Restaurant");
        ParseTestHelper::clearClass("Person");
        $restaurantLocations = ["Djibouti", "Ouagadougou"];
        $restaurantRatings = [5, 3];
        $numberOFRestaurantObjects = count($restaurantLocations);

        $personHomeTown = ["Djibouti", "Ouagadougou", "Detroit"];
        $personName = ["Bob", "Tom", "Billy"];
        $numberOfPersonObjects = count($personHomeTown);

        $this->saveObjects(
            $numberOFRestaurantObjects, function ($i) use ($restaurantRatings, $restaurantLocations) {
                $restaurant = ParseObject::create("Restaurant");
                $restaurant->set("ratings", $restaurantRatings[$i]);
                $restaurant->set("location", $restaurantLocations[$i]);

                return $restaurant;
            }
        );

        $this->saveObjects(
            $numberOfPersonObjects, function ($i) use ($personHomeTown, $personName) {
                $person = ParseObject::create("Person");
                $person->set("hometown", $personHomeTown[$i]);
            $person->set("name", $personName[$i]);

                return $person;
            }
        );
    }

    public function dtestMatchesKeyInQuery()
    {
        $this->provideTestObjectsForKeyInQuery();
        $subQuery = new ParseQuery("Restaurant");
        $subQuery->greaterThan("ratings", 4);

        $query = new ParseQuery("Person");
        $query->matchesKeyInQuery("hometown", "location", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            "Bob", $results[0]->get("name"),
            'Did not return the correct object.'
        );
    }

    public function dtestDoesNotMatchKeyInQuery()
    {
        $this->provideTestObjectsForKeyInQuery();
        $subQuery = new ParseQuery("Restaurant");
        $subQuery->greaterThanOrEqualTo("ratings", 3);

        $query = new ParseQuery("Person");
        $query->doesNotmatchKeyInQuery("hometown", "location", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            "Billy", $results[0]->get("name"),
            'Did not return the correct object.'
        );
    }

    public function dtestOrQueries()
    {
        $this->provideTestObjects(10);
        $subQuery1 = new ParseQuery("TestObject");
        $subQuery1->lessThan("foo", "bar2");
        $subQuery2 = new ParseQuery("TestObject");
        $subQuery2->greaterThan("foo", "bar5");

        $mainQuery = ParseQuery::orQueries([$subQuery1, $subQuery2]);
        $results = $mainQuery->find();
        $length = count($results);
        $this->assertEquals(
            6, $length,
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < $length; $i++) {
            $this->assertTrue(
                $results[$i]->get("foo") < "bar2" ||
                $results[$i]->get("foo") > "bar5",
                'Did not return the correct object.'
            );
        }
    }

    public function dtestComplexQueries()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $this->saveObjects(
            10, function ($i) {
                $child = new ParseObject("Child");
                $child->set("x", $i);
                $parent = new ParseObject("PParent");
                $parent->set("y", $i);
                $parent->set("child", $child);

                return $parent;
            }
        );
        $subQuery = new ParseQuery("Child");
        $subQuery->equalTo("x", 4);
        $query1 = new ParseQuery("PParent");
        $query1->matchesQuery("child", $subQuery);
        $query2 = new ParseQuery("PParent");
        $query2->lessThan("y", 2);

        $orQuery = ParseQuery::orQueries([$query1, $query2]);
        $results = $orQuery->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestEach()
    {
        ParseTestHelper::clearClass("Object");
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);

        $values = [];
        $query->each(
            function ($obj) use (&$values) {
                $values[] = $obj->get("x");
            }, 10
        );

        $valuesLength = count($values);
        $this->assertEquals(
            $count, $valuesLength,
            'Did not return correct number of objects.'
        );
        sort($values);
        for ($i = 0; $i < $valuesLength; $i++) {
            $this->assertEquals(
                $i + 1, $values[$i],
                'Did not return the correct object.'
            );
        }
    }

    public function dtestEachFailsWithOrder()
    {
        ParseTestHelper::clearClass("Object");
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);
        $query->ascending("x");
        $this->setExpectedException('\Exception', 'sort');
        $query->each(
            function ($obj) {
            }
        );
    }

    public function dtestEachFailsWithSkip()
    {
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);
        $query->skip(5);
        $this->setExpectedException('\Exception', 'skip');
        $query->each(
            function ($obj) {
            }
        );
    }

    public function dtestEachFailsWithLimit()
    {
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);
        $query->limit(5);
        $this->setExpectedException('\Exception', 'limit');
        $query->each(
            function ($obj) {
            }
        );
    }

    public function nytestContainsAllNumberArrayQueries()
    {
        ParseTestHelper::clearClass("NumberSet");
        $numberSet1 = new ParseObject("NumberSet");
        $numberSet1->setArray("numbers", [1, 2, 3, 4, 5]);
        $numberSet2 = new ParseObject("NumberSet");
        $numberSet2->setArray("numbers", [1, 3, 4, 5]);
        $numberSet1->save();
        $numberSet2->save();

        $query = new ParseQuery("NumberSet");
        $query->containsAll("numbers", [1, 2, 3]);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function nytestContainsAllStringArrayQueries()
    {
        ParseTestHelper::clearClass("StringSet");
        $stringSet1 = new ParseObject("StringSet");
        $stringSet1->setArray("strings", ["a", "b", "c", "d", "e"]);
        $stringSet1->save();
        $stringSet2 = new ParseObject("StringSet");
        $stringSet2->setArray("strings", ["a", "c", "d", "e"]);
        $stringSet2->save();

        $query = new ParseQuery("StringSet");
        $query->containsAll("strings", ["a", "b", "c"]);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function nytestContainsAllDateArrayQueries()
    {
        ParseTestHelper::clearClass("DateSet");
        $dates1 = [
                new DateTime("2013-02-01T00:00:00Z"),
                new DateTime("2013-02-02T00:00:00Z"),
                new DateTime("2013-02-03T00:00:00Z"),
                new DateTime("2013-02-04T00:00:00Z"),
        ];
        $dates2 = [
                new DateTime("2013-02-01T00:00:00Z"),
                new DateTime("2013-02-03T00:00:00Z"),
                new DateTime("2013-02-04T00:00:00Z"),
        ];

        $obj1 = ParseObject::create("DateSet");
        $obj1->setArray("dates", $dates1);
        $obj1->save();
        $obj2 = ParseObject::create("DateSet");
        $obj2->setArray("dates", $dates2);
        $obj2->save();

        $query = new ParseQuery("DateSet");
        $query->containsAll(
            "dates", [
                new DateTime("2013-02-01T00:00:00Z"),
                new DateTime("2013-02-02T00:00:00Z"),
                new DateTime("2013-02-03T00:00:00Z"),
            ]
        );
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return correct number of objects.'
        );
    }

    public function nytestContainsAllObjectArrayQueries()
    {
        ParseTestHelper::clearClass("MessageSet");
        $messageList = [];
        $this->saveObjects(
            4, function ($i) use (&$messageList) {
                $messageList[] = ParseObject::create("TestObject");
                $messageList[$i]->set("i", $i);

                return $messageList[$i];
            }
        );
        $messageSet1 = ParseObject::create("MessageSet");
        $messageSet1->setArray("messages", $messageList);
        $messageSet1->save();
        $messageSet2 = ParseObject::create("MessageSet");
        $messageSet2->setArray(
            "message",
            [$messageList[0], $messageList[1], $messageList[3]]
        );
        $messageSet2->save();

        $query = new ParseQuery("MessageSet");
        $query->containsAll("messages", [$messageList[0], $messageList[2]]);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function nytestContainedInObjectArrayQueries()
    {
        $messageList = [];
        $this->saveObjects(
            4, function ($i) use (&$messageList) {
                $message = ParseObject::create("TestObject");
                if ($i > 0) {
                    $message->set("prior", $messageList[$i - 1]);
                }
                $messageList[] = $message;

                return $message;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->containedIn("prior", [$messageList[0], $messageList[2]]);
        $results = $query->find();
        $this->assertEquals(
            2, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestContainedInQueries()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            10, function ($i) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->containedIn("number", [3, 5, 7, 9, 11]);
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestNotContainedInQueries()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            10, function ($i) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->notContainedIn("number", [3, 5, 7, 9, 11]);
        $results = $query->find();
        $this->assertEquals(
            6, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestObjectIdContainedInQueries()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $objects = [];
        $this->saveObjects(
            5, function ($i) use (&$objects) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $i);
                $objects[] = $boxedNumber;

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->containedIn(
            "objectId", [$objects[2]->getObjectId(),
                        $objects[3]->getObjectId(),
                        $objects[0]->getObjectId(),
                        "NONSENSE", ]
        );
        $query->ascending("number");
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            0, $results[0]->get("number"),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            2, $results[1]->get("number"),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            3, $results[2]->get("number"),
            'Did not return the correct object.'
        );
    }

    public function nytestStartsWith()
    {
        $someAscii = "\\E' !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTU".
                "VWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~'";
        $prefixes = ['zax', 'start', '', ''];
        $suffixes = ['qub', '', 'end', ''];
        $this->saveObjects(
            4, function ($i) use ($prefixes, $suffixes, $someAscii) {
                $obj = ParseObject::create("TestObject");
                $obj->set("myString", $prefixes[$i].$someAscii.$suffixes[$i]);

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->startsWith("myString", $someAscii);
        $results = $query->find();
        $this->assertEquals(
            2, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function provideTestObjectsForOrderBy()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $strings = ['a', 'b', 'c', 'd'];
        $numbers = [3, 1, 3, 2];
        for ($i = 0; $i < 4; $i++) {
            $obj = ParseObject::create("BoxedNumber");
            $obj->set('string', $strings[$i]);
            $obj->set('number', $numbers[$i]);
            // sleep to be sure about the order
            sleep(1);
            $obj->save();
        }
    }

    public function dtestOrderByAscNumberThenDescString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->ascending('number')->addDescending('string');
        $results = $query->find();
        $expected = [[1, 'b'], [2, 'd'], [3, 'c'], [3, 'a']];
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i][0], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1], $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestOrderByDescNumberThenAscString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->descending('number')->addAscending('string');
        $results = $query->find();
        $expected = [[3, 'a'], [3, 'c'], [2, 'd'], [1, 'b']];
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i][0], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1], $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestOrderByDescNumberAndString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->descending(['number', 'string']);
        $results = $query->find();
        $expected = [[3, 'c'], [3, 'a'], [2, 'd'], [1, 'b']];
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i][0], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1], $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestCannotOrderByPassword()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->ascending('_password');
        $this->setExpectedException('Parse\ParseException', "", 105);
        $query->find();
    }

    public function dtestOrderByCreatedAtAsc()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->ascending('createdAt');
        $query->find();
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [3, 1, 3, 2];
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestOrderByCreatedAtDesc()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->descending('createdAt');
        $query->find();
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [2, 3, 1, 3];
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestOrderByUpdatedAtAsc()
    {
        $numbers = [3, 1, 2];
        $objects = [];
        $this->saveObjects(
            3, function ($i) use ($numbers, &$objects) {
                $obj = ParseObject::create("TestObject");
                $obj->set('number', $numbers[$i]);
                $objects[] = $obj;

                return $obj;
            }
        );
        $objects[1]->set('number', 4);
        sleep(1);
        $objects[1]->save();
        $query = new ParseQuery("TestObject");
        $query->ascending('updatedAt');
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [3, 2, 4];
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestOrderByUpdatedAtDesc()
    {
        $numbers = [3, 1, 2];
        $objects = [];
        $this->saveObjects(
            3, function ($i) use ($numbers, &$objects) {
                $obj = ParseObject::create("TestObject");
                $obj->set('number', $numbers[$i]);
                $objects[] = $obj;
                sleep(1);
                return $obj;
            }
        );
        $objects[1]->set('number', 4);
        sleep(1);
        $objects[1]->save();
        $query = new ParseQuery("TestObject");
        $query->descending('updatedAt');
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [4, 3, 2];
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function dtestSelectKeysQuery()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $obj->save();
        $query = new ParseQuery("TestObject");
        $query->select('foo');
        $result = $query->first();
        $this->assertEquals(
            'baz', $result->get('foo'),
            'Did not return the correct object.'
        );
        $this->setExpectedException('\Exception', 'Call fetch()');
        $result->get('bar');
    }

    public function dtestGetWithoutError()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $this->assertEquals(
            'baz', $obj->get('foo'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            1, $obj->get('bar'),
            'Did not return the correct object.'
        );
        $obj->save();
    }
    public function dtestSelectKeysQueryArrayArg()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $obj->save();
        $query = new ParseQuery("TestObject");
        $query->select(['foo', 'bar']);
        $result = $query->first();
        $this->assertEquals(
            'baz', $result->get('foo'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            1, $result->get('bar'),
            'Did not return the correct object.'
        );
    }

    public function dtestExists()
    {
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->exists('x');
        $results = $query->find();
        $this->assertEquals(
            5, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestDoesNotExist()
    {
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->doesNotExist('x');
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestExistsRelation()
    {
        ParseTestHelper::clearClass("Item");
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $item = ParseObject::create("Item");
                    $item->set('e', $i);
                    $obj->set('e', $item);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->exists('e');
        $results = $query->find();
        $this->assertEquals(
            5, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestDoesNotExistRelation()
    {
        ParseTestHelper::clearClass("Item");
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $item = ParseObject::create("Item");
                    $item->set('x', $i);
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->doesNotExist('x');
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestDoNotIncludeRelation()
    {
        $child = ParseObject::create("Child");
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create("PParent");
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $query = new ParseQuery('PParent');
        $result = $query->first();
        $this->setExpectedException('\Exception', 'Call fetch()');
        $result->get('child')->get('x');
    }

    public function dtestIncludeRelation()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $child = ParseObject::create("Child");
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create("PParent");
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $query = new ParseQuery('PParent');
        $query->includeKey('child');
        $result = $query->first();
        $this->assertEquals(
            $result->get('y'), $result->get('child')->get('x'),
            'Object should be fetched.'
        );
        $this->assertEquals(
            1, $result->get('child')->get('x'),
            'Object should be fetched.'
        );
    }

    public function dtestNestedIncludeRelation()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        ParseTestHelper::clearClass("GrandParent");
        $child = ParseObject::create("Child");
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create("PParent");
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $grandParent = ParseObject::create("GrandParent");
        $grandParent->set('pparent', $parent);
        $grandParent->set('z', 1);
        $grandParent->save();

        $query = new ParseQuery('GrandParent');
        $query->includeKey('pparent.child');
        $result = $query->first();
        $this->assertEquals(
            $result->get('z'), $result->get('pparent')->get('y'),
            'Object should be fetched.'
        );
        $this->assertEquals(
            $result->get('z'),
            $result->get('pparent')->get('child')->get('x'),
            'Object should be fetched.'
        );
    }

    public function nytestIncludeArrayRelation()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $children = [];
        $this->saveObjects(
            5, function ($i) use (&$children) {
                $child = ParseObject::create("Child");
                $child->set('x', $i);
                $children[] = $child;

                return $child;
            }
        );
        $parent = ParseObject::create("PParent");
        $parent->setArray('children', $children);
        $parent->save();

        $query = new ParseQuery("PParent");
        $query->includeKey('children');
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return correct number of objects.'
        );
        $children = $result[0]->get('children');
        $length = count($children);
        for ($i = 0; $i < $length; $i++) {
            $this->assertEquals(
                $i, $children[$i]->get('x'),
                'Object should be fetched.'
            );
        }
    }

    public function dtestIncludeWithNoResults()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $query = new ParseQuery("PParent");
        $query->includeKey('children');
        $result = $query->find();
        $this->assertEquals(
            0, count($result),
            'Did not return correct number of objects.'
        );
    }

    public function dtestIncludeWithNonExistentKey()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $parent = ParseObject::create("PParent");
        $parent->set('foo', 'bar');
        $parent->save();

        $query = new ParseQuery("PParent");
        $query->includeKey('child');
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestIncludeOnTheWrongKeyType()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $parent = ParseObject::create("PParent");
        $parent->set('foo', 'bar');
        $parent->save();

        $query = new ParseQuery("PParent");
        $query->includeKey('foo');
        $this->setExpectedException('Parse\ParseException', '', 102);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function dtestIncludeWhenOnlySomeObjectsHaveChildren()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("PParent");
        $child = ParseObject::create('Child');
        $child->set('foo', 'bar');
        $child->save();
        $this->saveObjects(
            4, function ($i) use ($child) {
                $parent = ParseObject::create('PParent');
                $parent->set('num', $i);
                if ($i & 1) {
                    $parent->set('child', $child);
                }

                return $parent;
            }
        );

        $query = new ParseQuery('PParent');
        $query->includeKey(['child']);
        $query->ascending('num');
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        $length = count($results);
        for ($i = 0; $i < $length; $i++) {
            if ($i & 1) {
                $this->assertEquals(
                    'bar', $results[$i]->get('child')->get('foo'),
                    'Object should be fetched'
                );
            } else {
                $this->assertEquals(
                    null, $results[$i]->get('child'),
                    'Should not have child'
                );
            }
        }
    }

    public function dtestIncludeMultipleKeys()
    {
        ParseTestHelper::clearClass("Foo");
        ParseTestHelper::clearClass("Bar");
        ParseTestHelper::clearClass("PParent");
        $foo = ParseObject::create('Foo');
        $foo->set('rev', 'oof');
        $foo->save();
        $bar = ParseObject::create('Bar');
        $bar->set('rev', 'rab');
        $bar->save();

        $parent = ParseObject::create('PParent');
        $parent->set('foofoo', $foo);
        $parent->set('barbar', $bar);
        $parent->save();

        $query = new ParseQuery('PParent');
        $query->includeKey(['foofoo', 'barbar']);
        $result = $query->first();
        $this->assertEquals(
            'oof', $result->get('foofoo')->get('rev'),
            'Object should be fetched'
        );
        $this->assertEquals(
            'rab', $result->get('barbar')->get('rev'),
            'Object should be fetched'
        );
    }

    public function dtestEqualToObject()
    {
        ParseTestHelper::clearClass("Item");
        ParseTestHelper::clearClass("Container");
        $items = [];
        $this->saveObjects(
            2, function ($i) use (&$items) {
                $items[] = ParseObject::create("Item");
                $items[$i]->set('x', $i);

                return $items[$i];
            }
        );
        $this->saveObjects(
            2, function ($i) use ($items) {
                $container = ParseObject::create("Container");
                $container->set('item', $items[$i]);

                return $container;
            }
        );
        $query = new ParseQuery("Container");
        $query->equalTo('item', $items[0]);
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return the correct object.'
        );
    }

    public function dtestEqualToNull()
    {
        $this->saveObjects(
            10, function ($i) {
                $obj = ParseObject::create('TestObject');
                $obj->set('num', $i);

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->equalTo('num', null);
        $results = $query->find();
        $this->assertEquals(
            0, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function provideTimeTestObjects()
    {
        ParseTestHelper::clearClass("TestObject");
        $items = [];
        $this->saveObjects(
            3, function ($i) use (&$items) {
                $TestObject = ParseObject::create('TestObject');
                $TestObject->set('name', 'item'.$i);
                $TestObject->set('time', new DateTime());
                sleep(1);
                $items[] = $TestObject;

                return $TestObject;
            }
        );

        return $items;
    }

    public function dtestTimeEquality()
    {
        $items = $this->provideTimeTestObjects();
        $query = new ParseQuery('TestObject');
        $query->equalTo('time', $items[1]->get('time'));
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals('item1', $results[0]->get('name'));
    }

    public function dtestTimeLessThan()
    {
        $items = $this->provideTimeTestObjects();
        $query = new ParseQuery('TestObject');
        $query->lessThan('time', $items[2]->get('time'));
        $results = $query->find();
        $this->assertEquals(
            2, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function nytestRestrictedGetFailsWithoutMasterKey()
    {
        $obj = ParseObject::create("TestObject");
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();

        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'not found');
        $objAgain = $query->get($obj->getObjectId());
    }

    public function nytestRestrictedGetWithMasterKey()
    {
        $obj = ParseObject::create("TestObject");
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();

        $query = new ParseQuery("TestObject");
        $objAgain = $query->get($obj->getObjectId(), true);
        $this->assertEquals($obj->getObjectId(), $objAgain->getObjectId());
    }

    public function nytestRestrictedCount()
    {
        $obj = ParseObject::create("TestObject");
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();

        $query = new ParseQuery("TestObject");
        $count = $query->count();
        $this->assertEquals(0, $count);
        $count = $query->count(true);
        $this->assertEquals(1, $count);
    }
}
