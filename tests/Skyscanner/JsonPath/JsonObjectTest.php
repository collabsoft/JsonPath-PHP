<?php
/**
 * Copyright 2015 Skyscanner Ltd
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Tests;

use JsonPath\JsonObject;

/**
 * Class JsonObjectTest
 * @author Alessio Linares
 */
class JsonPathTest extends \PHPUnit_Framework_TestCase
{
    private $json = '
{ "store": {
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century",
        "price": 8.95,
        "available": true
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour",
        "price": 12.99,
        "available": false
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3",
        "price": 8.99,
        "available": true
      },
      { "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8",
        "price": 22.99,
        "available": false
      }
    ],
    "bicycle": {
      "color": "red",
      "price": 19.95,
      "available": true,
      "model": null
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ],
  "Bike models": [
    1,
    2,
    3
  ]
}
';

    /**
     * testGet
     *
     * @param array $expected expected
     * @param array $jsonObject jsonObject
     * @param string $jsonPath jsonPath
     *
     * @return void
     *
     * @dataProvider testGetProvider
     */
    public function testGet($expected, $jsonPath, $testReference = true)
    {
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals($expected, $result);

        if ($result !== false && $testReference) {
            // Test that all elements in the result are
            // references to the contents in the object
            foreach ($result as &$element) {
                $element = 'NaN';
            }

            $result2 = $jsonObject->get($jsonPath);
            foreach ($result2 as &$element) {
                $this->assertEquals('NaN', $element);
            }
        }
    }

    public function testGetProvider()
    {
        return array(
            array(
                array(19.95),
                "$.store.bicycle.price"
            ),
            array(
                array(
                    array(
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null
                    )
                ),
                "$.store.bicycle"
            ),
            array(
                false,
                "$.store.bicycl"
            ),
            array(
                array(
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[*].price"
            ),
            array(
                false,
                "$.store.book[7]"
            ),
            array(
                array(
                    12.99,
                    8.99
                ),
                "$.store.book[1, 2].price"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*][category, author]"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*]['category', \"author\"]"
            ),
            array(
                array(
                    8.95,
                    8.99
                ),
                "$.store.book[0:3:2].price"
            ),
            array(
                false,
                "$.store.bicycle.price[2]"
            ),
            array(
                false,
                "$.store.bicycle.price.*"
            ),
            array(
                array(
                    "red",
                    19.95,
                    true,
                    null
                ),
                "$.store.bicycle.*"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$..*.price"
            ),
            array(
                array(
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[?(@.category == 'fiction')].price"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    8.99
                ),
                "$..*[?(@.available == true)].price"
            ),
            array(
                array(
                    12.99,
                    22.99
                ),
                "$..*[?(@.available == false)].price"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10)].title"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10.0)].title"
            ),
            array(
                array(
                    "Sword of Honour",
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.price > 10)].title"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$..*[?(@.author =~ /.*Tolkien/)].title"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 4)].color"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 4.0)].color"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.author == $.authors[3])].title"
            ),
            array(
                array(
                    "red",
                    "J. R. R. Tolkien"
                ),
                "$..*[?(@.price >= 19.95)][author, color]"
            ),
            array(
                array(
                    19.95,
                    8.99
                ),
                "$..*[?(@.category == 'fiction' and @.price < 10 or @.color == \"red\")].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(not @.category == 'fiction')].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(@.category != 'fiction')].price"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.color)].color"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(not @..price or @..color == 'red')].available"
            ),
            array(
                false,
                "$.store[?(@.price.length == 3)]"
            ),
            array(
                array(
                    19.95
                ),
                "$.store[?(@.color.length == 3)].price"
            ),
            array(
                false,
                "$.store[?(@.color.length == 5)].price"
            ),
            array(
                array(
                    array(
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null
                    )
                ),
                "$.store[?(@.*.length == 3)]",
                false
            ),
            array(
                array(
                    "red"
                ),
                "$.store..*[?(@..model == null)].color"
            ),
            array(
                array(
                    array(1, 2, 3)
                ),
                "$['Bike models']"
            ),
            array(
                array(
                    array(1, 2, 3)
                ),
                '$["Bike models"]'
            )
        );
    }

    /**
     * testSmartGet
     *
     * @param array $expected expected
     * @param array $jsonObject jsonObject
     * @param string $jsonPath jsonPath
     *
     * @return void
     *
     * @dataProvider testSmartGetProvider
     */
    public function testSmartGet($expected, $jsonPath)
    {
        $jsonObject = new JsonObject($this->json, true);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals($expected, $result);
    }

    public function testSmartGetProvider()
    {
        return array(
            array(
                19.95,
                "$.store.bicycle.price"
            ),
            array(
                array(
                    "color" => "red",
                    "price" => 19.95,
                    "available" => true,
                    "model" => null
                ),
                "$.store.bicycle"
            ),
            array(
                false,
                "$.store.bicycl"
            ),
            array(
                array(
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[*].price"
            ),
            array(
                false,
                "$.store.book[7]"
            ),
            array(
                array(
                    12.99,
                    8.99
                ),
                "$.store.book[1, 2].price"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*][category, author]"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*]['category', \"author\"]"
            ),
            array(
                array(
                    8.95,
                    8.99
                ),
                "$.store.book[0:3:2].price"
            ),
            array(
                false,
                "$.store.bicycle.price[2]"
            ),
            array(
                false,
                "$.store.bicycle.price.*"
            ),
            array(
                array(
                    "red",
                    19.95,
                    true,
                    null
                ),
                "$.store.bicycle.*"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$..*.price"
            ),
            array(
                array(
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[?(@.category == 'fiction')].price"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    8.99
                ),
                "$..*[?(@.available == true)].price"
            ),
            array(
                array(
                    12.99,
                    22.99
                ),
                "$..*[?(@.available == false)].price"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10)].title"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10.0)].title"
            ),
            array(
                array(
                    "Sword of Honour",
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.price > 10)].title"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$..*[?(@.author =~ /.*Tolkien/)].title"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 4)].color"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 4.0)].color"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.author == $.authors[3])].title"
            ),
            array(
                array(
                    "red",
                    "J. R. R. Tolkien"
                ),
                "$..*[?(@.price >= 19.95)][author, color]"
            ),
            array(
                array(
                    19.95,
                    8.99
                ),
                "$..*[?(@.category == 'fiction' and @.price < 10 or @.color == \"red\")].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(not @.category == 'fiction')].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(@.category != 'fiction')].price"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.color)].color"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(not @..price or @..color == 'red')].available"
            ),
            array(
                false,
                "$.store[?(@.price.length == 3)]"
            ),
            array(
                array(
                    19.95
                ),
                "$.store[?(@.color.length == 3)].price"
            ),
            array(
                false,
                "$.store[?(@.color.length == 5)].price"
            ),
            array(
                array(
                    array(
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null
                    )
                ),
                "$.store[?(@.*.length == 3)]",
                false
            ),
            array(
                array(
                    "red"
                ),
                "$.store..*[?(@..model == null)].color"
            ),
            array(
                array(1, 2, 3),
                "$['Bike models']"
            ),
            array(
                array(1, 2, 3),
                '$["Bike models"]'
            )
        );
    }

    public function testWithSmartGet()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * testSet
     *
     * @param bool $smartGet smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testSet($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->set('$.authors', array("Patrick Rothfuss", "Trudi Canavan"));
        $this->assertEquals(array("Patrick Rothfuss", "Trudi Canavan"), $jsonObject->get('$.authors[*]'));
    }

    public function testGetValue()
    {
        $array = json_decode($this->json, true);
        $jsObject = new JsonObject($array);
        $this->assertEquals($array, $jsObject->getValue());
        $jsObject = new JsonObject($this->json);
        $this->assertEquals($array, $jsObject->getValue());
        $object = json_decode($this->json);
        $jsObject = new JsonObject($object);
        $this->assertEquals($array, $jsObject->getValue());
    }

    /**
     * testAdd
     *
     * @param bool $smartGet smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testAdd($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->add('$.authors', 'Trudi Canavan');
        $this->assertEquals(array("Nigel Rees", "Evelyn Waugh", "Herman Melville", "J. R. R. Tolkien", "Trudi Canavan"), $jsonObject->get('$.authors[*]'));

        $jsonObject->add('$.store.bicycle', 'BMX', 'type');
        $expected = array(
            array(
                'color' => 'red',
                'price' => 19.95,
                'type' => 'BMX',
                'available' => true,
                'model' => null
            )
        );
        $expected = $smartGet ? $expected[0] : $expected;
        $this->assertEquals(
            $expected,
            $jsonObject->get('$.store.bicycle')
        );
    }

    /**
     * testRemove
     *
     * @param bool $smartGet smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testRemove($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->remove('$..*[?(@.price)]', 'price')->remove('$..*', 'available');
        $jsonObject->remove('$', 'Bike models');
        $this->assertEquals(
            json_decode(
                '{ "store": {
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century"
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour"
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3"
      },
      { "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8"
      }
    ],
    "bicycle": {
      "color": "red",
      "model": null
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ]
}'
                , true
            )
            , $jsonObject->getValue()
        );
    }

    public function testGetJson()
    {
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 41, 'size')
            ->add('$', 'black', 'color')
            ->add('$', array(), 'meta')
            ->add('$.meta', 0xfe34, 'code');
        $this->assertEquals('{"size":41,"color":"black","meta":{"code":65076}}', $jsonObject->getJson());
    }

    public function testMagickMethods()
    {
        $jsonObject = new JsonObject($this->json);
        $this->assertEquals(array('0-553-21311-3', '0-395-19395-8'), $jsonObject->{'$..*.isbn'});
        $jsonObject->{'$.store.bicycle.color'} = 'green';
        $this->assertEquals(array('green'), $jsonObject->{'$.store.bicycle.color'});
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 41, 'size')
            ->add('$', 'black', 'color')
            ->add('$', array(), 'meta')
            ->add('$.meta', 0xfe34, 'code');
        $this->assertEquals('{"size":41,"color":"black","meta":{"code":65076}}', (string) $jsonObject);
    }

    /**
     * testConstructErrors
     *
     * @param string $jsonPath jsonPath
     *
     * @return void
     * @dataProvider testConstructErrorsProvider
     */
    public function testConstructErrors($json)
    {
        $exception = null;
        try {
            $jsonObject = new JsonObject($json);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertEquals($exception->getMessage(), "Invalid json");
    }

    public function testConstructErrorsProvider()
    {
        return array(
            array(5),
            array('{"invalid": json}')
        );
    }

    /**
     * testParsingErrors
     *
     * @param string $jsonPath jsonPath
     *
     * @return void
     * @dataProvider testParsingErrorsProvider
     */
    public function testParsingErrors($jsonPath)
    {
        $jsonObject = new JsonObject($this->json);
        $exception = null;
        try {
            $jsonObject->get($jsonPath);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertEquals($exception->getMessage(), "Invalid JsonPath");
    }

    public function testParsingErrorsProvider()
    {
        return array(
            array('$[store'),
            array('$[{fail}]'),
            array('a.bc')
        );
    }

    public function testGetJsonObjects()
    {
        $jsonObject = new JsonObject($this->json);
        $childs = $jsonObject->getJsonObjects('$.store.book[*]');
        foreach ($childs as $key => $book) {
            $book->set('$.price', $key);
            $this->assertEquals($jsonObject->{'$.store.book[' . $key . ']'}[0], $book->getValue());
        }
        $this->assertEquals(4, count($childs));

        $jsonObject = new JsonObject($this->json, true);
        $bike = $jsonObject->getJsonObjects('$.store.bicycle');
        $bike->set('$.price', 412);
        $this->assertEquals($jsonObject->{'$.store.bicycle'}, $bike->getValue());

        $this->assertFalse($jsonObject->getJsonObjects('$.abc'));
    }
}