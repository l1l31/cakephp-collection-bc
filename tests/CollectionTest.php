<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Collection;

use ArrayIterator;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;
// use Cake\TestSuite\TestCase;
use PHPUnit\Framework\TestCase;
use NoRewindIterator;

/**
 * CollectionTest
 */
class CollectionTest extends TestCase
{

    /**
     * Tests that it is possible to convert an array into a collection
     *
     * @return void
     */
    public function testArrayIsWrapped()
    {
        $items = array(1, 2, 3);
        $collection = new Collection($items);
        $this->assertEquals($items, iterator_to_array($collection));
    }

    /**
     * Tests that it is possible to convert an iterator into a collection
     *
     * @return void
     */
    public function testIteratorIsWrapped()
    {
        $items = new \ArrayObject(array(1, 2, 3));
        $collection = new Collection($items);
        $this->assertEquals(iterator_to_array($items), iterator_to_array($collection));
    }

    /**
     * Test running a method over all elements in the collection
     *
     * @return void
     */
    public function testEach()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a');
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b');
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c');
        $collection->each($callable);
    }

    /**
     * Test filter() with no callback.
     *
     * @return void
     */
    public function testFilterNoCallback()
    {
        $items = array(1, 2, 0, 3, false, 4, null, 5, '');
        $collection = new Collection($items);
        $result = $collection->filter()->toArray();
        $expected = array(1, 2, 3, 4, 5);
        $this->assertEquals($expected, array_values($result));
    }

    /**
     * Tests that it is possible to chain filter() as it returns a collection object
     *
     * @return void
     */
    public function testFilterChaining()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->once())
            ->method('__invoke')
            ->with(3, 'c');
        $filtered = $collection->filter(function ($value, $key, $iterator) {
            return $value > 2;
        });

        $this->assertInstanceOf('Cake\Collection\Collection', $filtered);
        $filtered->each($callable);
    }

    /**
     * Tests reject
     *
     * @return void
     */
    public function testReject()
    {
        $collection = new Collection(array());
        $result = $collection->reject(function ($v) {
            return false;
        });
        $this->assertSame(array(), iterator_to_array($result));

        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $result = $collection->reject(function ($v, $k, $items) use ($collection) {
            $this->assertSame($collection->getInnerIterator(), $items);

            return $v > 2;
        });
        $this->assertEquals(array('a' => 1, 'b' => 2), iterator_to_array($result));
        $this->assertInstanceOf('Cake\Collection\Collection', $result);
    }

    /**
     * Tests every when the callback returns true for all elements
     *
     * @return void
     */
    public function testEveryReturnTrue()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(true));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(true));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c')
            ->will($this->returnValue(true));
        $this->assertTrue($collection->every($callable));
    }

    /**
     * Tests every when the callback returns false for one of the elements
     *
     * @return void
     */
    public function testEveryReturnFalse()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(true));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(false));
        $callable->expects($this->exactly(2))->method('__invoke');
        $this->assertFalse($collection->every($callable));

        $items = array();
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->never())
            ->method('__invoke');
        $this->assertTrue($collection->every($callable));
    }

    /**
     * Tests some() when one of the calls return true
     *
     * @return void
     */
    public function testSomeReturnTrue()
    {
        $collection = new Collection(array());
        $result = $collection->some(function ($v) {
            return true;
        });
        $this->assertFalse($result);

        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(false));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(true));
        $callable->expects($this->exactly(2))->method('__invoke');
        $this->assertTrue($collection->some($callable));
    }

    /**
     * Tests some() when none of the calls return true
     *
     * @return void
     */
    public function testSomeReturnFalse()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(false));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(false));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c')
            ->will($this->returnValue(false));
        $this->assertFalse($collection->some($callable));
    }

    /**
     * Tests contains
     *
     * @return void
     */
    public function testContains()
    {
        $collection = new Collection(array());
        $this->assertFalse($collection->contains('a'));

        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $this->assertTrue($collection->contains(2));
        $this->assertTrue($collection->contains(1));
        $this->assertFalse($collection->contains(10));
        $this->assertFalse($collection->contains('2'));
    }

    /**
     * Tests map
     *
     * @return void
     */
    public function testMap()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $map = $collection->map(function ($v, $k, $it) use ($collection) {
            $this->assertSame($collection->getInnerIterator(), $it);

            return $v * $v;
        });
        $this->assertInstanceOf('Cake\Collection\Iterator\ReplaceIterator', $map);
        $this->assertEquals(array('a' => 1, 'b' => 4, 'c' => 9), iterator_to_array($map));
    }

    /**
     * Tests reduce with initial value
     *
     * @return void
     */
    public function testReduceWithInitialValue()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(10, 1, 'a')
            ->will($this->returnValue(11));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(11, 2, 'b')
            ->will($this->returnValue(13));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(13, 3, 'c')
            ->will($this->returnValue(16));
        $this->assertEquals(16, $collection->reduce($callable, 10));
    }

    /**
     * Tests reduce without initial value
     *
     * @return void
     */
    public function testReduceWithoutInitialValue()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 2, 'b')
            ->will($this->returnValue(3));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(3, 3, 'c')
            ->will($this->returnValue(6));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(6, 4, 'd')
            ->will($this->returnValue(10));
        $this->assertEquals(10, $collection->reduce($callable));
    }

    /**
     * Tests extract
     *
     * @return void
     */
    public function testExtract()
    {
        $items = array(array('a' => array('b' => array('c' => 1))), 2);
        $collection = new Collection($items);
        $map = $collection->extract('a.b.c');
        $this->assertInstanceOf('Cake\Collection\Iterator\ExtractIterator', $map);
        $this->assertEquals(array(1, null), iterator_to_array($map));
    }

    /**
     * Tests sort
     *
     * @return void
     */
    public function testSortString()
    {
        $items = array(
            array('a' => array('b' => array('c' => 4))),
            array('a' => array('b' => array('c' => 10))),
            array('a' => array('b' => array('c' => 6)))
        );
        $collection = new Collection($items);
        $map = $collection->sortBy('a.b.c');
        $this->assertInstanceOf('Cake\Collection\Collection', $map);
        $expected = array(
            array('a' => array('b' => array('c' => 10))),
            array('a' => array('b' => array('c' => 6))),
            array('a' => array('b' => array('c' => 4))),
        );
        $this->assertEquals($expected, $map->toList());
    }

    /**
     * Tests max
     *
     * @return void
     */
    public function testMax()
    {
        $items = array(
            array('a' => array('b' => array('c' => 4))),
            array('a' => array('b' => array('c' => 10))),
            array('a' => array('b' => array('c' => 6)))
        );
        $collection = new Collection($items);
        $this->assertEquals(array('a' => array('b' => array('c' => 10))), $collection->max('a.b.c'));

        $callback = function ($e) {
            return $e['a']['b']['c'] * - 1;
        };
        $this->assertEquals(array('a' => array('b' => array('c' => 4))), $collection->max($callback));
    }

    /**
     * Tests min
     *
     * @return void
     */
    public function testMin()
    {
        $items = array(
            array('a' => array('b' => array('c' => 4))),
            array('a' => array('b' => array('c' => 10))),
            array('a' => array('b' => array('c' => 6)))
        );
        $collection = new Collection($items);
        $this->assertEquals(array('a' => array('b' => array('c' => 4))), $collection->min('a.b.c'));
    }

    /**
     * Tests groupBy
     *
     * @return void
     */
    public function testGroupBy()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'parent_id' => 10),
            array('id' => 2, 'name' => 'bar', 'parent_id' => 11),
            array('id' => 3, 'name' => 'baz', 'parent_id' => 10),
        );
        $collection = new Collection($items);
        $grouped = $collection->groupBy('parent_id');
        $expected = array(
            10 => array(
                array('id' => 1, 'name' => 'foo', 'parent_id' => 10),
                array('id' => 3, 'name' => 'baz', 'parent_id' => 10),
            ),
            11 => array(
                array('id' => 2, 'name' => 'bar', 'parent_id' => 11),
            )
        );
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);

        $grouped = $collection->groupBy(function ($element) {
            return $element['parent_id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests grouping by a deep key
     *
     * @return void
     */
    public function testGroupByDeepKey()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'thing' => array('parent_id' => 10)),
            array('id' => 2, 'name' => 'bar', 'thing' => array('parent_id' => 11)),
            array('id' => 3, 'name' => 'baz', 'thing' => array('parent_id' => 10)),
        );
        $collection = new Collection($items);
        $grouped = $collection->groupBy('thing.parent_id');
        $expected = array(
            10 => array(
                array('id' => 1, 'name' => 'foo', 'thing' => array('parent_id' => 10)),
                array('id' => 3, 'name' => 'baz', 'thing' => array('parent_id' => 10)),
            ),
            11 => array(
                array('id' => 2, 'name' => 'bar', 'thing' => array('parent_id' => 11)),
            )
        );
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests indexBy
     *
     * @return void
     */
    public function testIndexBy()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'parent_id' => 10),
            array('id' => 2, 'name' => 'bar', 'parent_id' => 11),
            array('id' => 3, 'name' => 'baz', 'parent_id' => 10),
        );
        $collection = new Collection($items);
        $grouped = $collection->indexBy('id');
        $expected = array(
            1 => array('id' => 1, 'name' => 'foo', 'parent_id' => 10),
            3 => array('id' => 3, 'name' => 'baz', 'parent_id' => 10),
            2 => array('id' => 2, 'name' => 'bar', 'parent_id' => 11),
        );
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);

        $grouped = $collection->indexBy(function ($element) {
            return $element['id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests indexBy with a deep property
     *
     * @return void
     */
    public function testIndexByDeep()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'thing' => array('parent_id' => 10)),
            array('id' => 2, 'name' => 'bar', 'thing' => array('parent_id' => 11)),
            array('id' => 3, 'name' => 'baz', 'thing' => array('parent_id' => 10)),
        );
        $collection = new Collection($items);
        $grouped = $collection->indexBy('thing.parent_id');
        $expected = array(
            10 => array('id' => 3, 'name' => 'baz', 'thing' => array('parent_id' => 10)),
            11 => array('id' => 2, 'name' => 'bar', 'thing' => array('parent_id' => 11)),
        );
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests countBy
     *
     * @return void
     */
    public function testCountBy()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'parent_id' => 10),
            array('id' => 2, 'name' => 'bar', 'parent_id' => 11),
            array('id' => 3, 'name' => 'baz', 'parent_id' => 10),
        );
        $collection = new Collection($items);
        $grouped = $collection->countBy('parent_id');
        $expected = array(
            10 => 2,
            11 => 1
        );
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);

        $grouped = $collection->countBy(function ($element) {
            return $element['parent_id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests shuffle
     *
     * @return void
     */
    public function testShuffle()
    {
        $data = array(1, 2, 3, 4);
        $collection = new Collection($data);
        $collection->shuffle();
        $this->assertCount(count($data), iterator_to_array($collection));

        foreach ($collection as $value) {
            $this->assertContains($value, $data);
        }
    }

    /**
     * Tests sample
     *
     * @return void
     */
    public function testSample()
    {
        $data = array(1, 2, 3, 4);
        $collection = new Collection($data);
        $collection->sample(2);
        $this->assertCount(2, iterator_to_array($collection));

        foreach ($collection as $value) {
            $this->assertContains($value, $data);
        }
    }

    /**
     * Test toArray method
     *
     * @return void
     */
    public function testToArray()
    {
        $data = array(1, 2, 3, 4);
        $collection = new Collection($data);
        $this->assertEquals($data, $collection->toArray());
    }

    /**
     * Test toList method
     *
     * @return void
     */
    public function testToList()
    {
        $data = array(100 => 1, 300 => 2, 500 => 3, 1 => 4);
        $collection = new Collection($data);
        $this->assertEquals(array_values($data), $collection->toList());
    }

    /**
     * Test json encoding
     *
     * @return void
     */
    public function testToJson()
    {
        $data = array(1, 2, 3, 4);
        $collection = new Collection($data);
        $this->assertEquals(json_encode($data), json_encode($collection));
    }

    /**
     * Tests that only arrays and Traversables are allowed in the constructor
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Only an array or \Traversable is allowed for Collection
     * @return void
     */
    public function testInvalidConstructorArgument()
    {
        new Collection('Derp');
    }

    /**
     * Tests that issuing a count will throw an exception
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testCollectionCount()
    {
        $data = array(1, 2, 3, 4);
        $collection = new Collection($data);
        $collection->count();
    }

    /**
     * Tests take method
     *
     * @return void
     */
    public function testTake()
    {
        $data = array(1, 2, 3, 4);
        $collection = new Collection($data);

        $taken = $collection->take(2);
        $this->assertEquals(array(1, 2), $taken->toArray());

        $taken = $collection->take(3);
        $this->assertEquals(array(1, 2, 3), $taken->toArray());

        $taken = $collection->take(500);
        $this->assertEquals(array(1, 2, 3, 4), $taken->toArray());

        $taken = $collection->take(1);
        $this->assertEquals(array(1), $taken->toArray());

        $taken = $collection->take();
        $this->assertEquals(array(1), $taken->toArray());

        $taken = $collection->take(2, 2);
        $this->assertEquals(array(2 => 3, 3 => 4), $taken->toArray());
    }

    /**
     * Tests match
     *
     * @return void
     */
    public function testMatch()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'thing' => array('parent_id' => 10)),
            array('id' => 2, 'name' => 'bar', 'thing' => array('parent_id' => 11)),
            array('id' => 3, 'name' => 'baz', 'thing' => array('parent_id' => 10)),
        );
        $collection = new Collection($items);
        $matched = $collection->match(array('thing.parent_id' => 10, 'name' => 'baz'));
        $this->assertEquals(array(2 => $items[2]), $matched->toArray());

        $matched = $collection->match(array('thing.parent_id' => 10));
        $this->assertEquals(
            array(0 => $items[0], 2 => $items[2]),
            $matched->toArray()
        );

        $matched = $collection->match(array('thing.parent_id' => 500));
        $this->assertEquals(array(), $matched->toArray());

        $matched = $collection->match(array('parent_id' => 10, 'name' => 'baz'));
        $this->assertEquals(array(), $matched->toArray());
    }

    /**
     * Tests firstMatch
     *
     * @return void
     */
    public function testFirstMatch()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'thing' => array('parent_id' => 10)),
            array('id' => 2, 'name' => 'bar', 'thing' => array('parent_id' => 11)),
            array('id' => 3, 'name' => 'baz', 'thing' => array('parent_id' => 10)),
        );
        $collection = new Collection($items);
        $matched = $collection->firstMatch(array('thing.parent_id' => 10));
        $this->assertEquals(
            array('id' => 1, 'name' => 'foo', 'thing' => array('parent_id' => 10)),
            $matched
        );

        $matched = $collection->firstMatch(array('thing.parent_id' => 10, 'name' => 'baz'));
        $this->assertEquals(
            array('id' => 3, 'name' => 'baz', 'thing' => array('parent_id' => 10)),
            $matched
        );
    }

    /**
     * Tests the append method
     *
     * @return void
     */
    public function testAppend()
    {
        $collection = new Collection(array(1, 2, 3));
        $combined = $collection->append(array(4, 5, 6));
        $this->assertEquals(array(1, 2, 3, 4, 5, 6), $combined->toArray(false));

        $collection = new Collection(array('a' => 1, 'b' => 2));
        $combined = $collection->append(array('c' => 3, 'a' => 4));
        $this->assertEquals(array('a' => 4, 'b' => 2, 'c' => 3), $combined->toArray());
    }

    /**
     * Tests the append method with iterator
     */
    public function testAppendIterator()
    {
        $collection = new Collection(array(1, 2, 3));
        $iterator = new ArrayIterator(array(4, 5, 6));
        $combined = $collection->append($iterator);
        $this->assertEquals(array(1, 2, 3, 4, 5, 6), $combined->toList());
    }

    /**
     * Tests that by calling compile internal iteration operations are not done
     * more than once
     *
     * @return void
     */
    public function testCompile()
    {
        $items = array('a' => 1, 'b' => 2, 'c' => 3);
        $collection = new Collection($items);
        $callable = $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(4));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(5));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c')
            ->will($this->returnValue(6));
        $compiled = $collection->map($callable)->compile();
        $this->assertEquals(array('a' => 4, 'b' => 5, 'c' => 6), $compiled->toArray());
        $this->assertEquals(array('a' => 4, 'b' => 5, 'c' => 6), $compiled->toArray());
    }

    /**
     * Tests converting a non rewindable iterator into a rewindable one using
     * the buffered method.
     *
     * @return void
     */
    public function testBuffered()
    {
        $items = new NoRewindIterator(new ArrayIterator(array('a' => 4, 'b' => 5, 'c' => 6)));
        $buffered = (new Collection($items))->buffered();
        $this->assertEquals(array('a' => 4, 'b' => 5, 'c' => 6), $buffered->toArray());
        $this->assertEquals(array('a' => 4, 'b' => 5, 'c' => 6), $buffered->toArray());
    }

    /**
     * Tests the combine method
     *
     * @return void
     */
    public function testCombine()
    {
        $items = array(
            array('id' => 1, 'name' => 'foo', 'parent' => 'a'),
            array('id' => 2, 'name' => 'bar', 'parent' => 'b'),
            array('id' => 3, 'name' => 'baz', 'parent' => 'a')
        );
        $collection = (new Collection($items))->combine('id', 'name');
        $expected = array(1 => 'foo', 2 => 'bar', 3 => 'baz');
        $this->assertEquals($expected, $collection->toArray());

        $expected = array('foo' => 1, 'bar' => 2, 'baz' => 3);
        $collection = (new Collection($items))->combine('name', 'id');
        $this->assertEquals($expected, $collection->toArray());

        $collection = (new Collection($items))->combine('id', 'name', 'parent');
        $expected = array('a' => array(1 => 'foo', 3 => 'baz'), 'b' => array(2 => 'bar'));
        $this->assertEquals($expected, $collection->toArray());

        $expected = array(
            '0-1' => array('foo-0-1' => '0-1-foo'),
            '1-2' => array('bar-1-2' => '1-2-bar'),
            '2-3' => array('baz-2-3' => '2-3-baz')
        );
        $collection = (new Collection($items))->combine(
            function ($value, $key) {
                return $value['name'] . '-' . $key;
            },
            function ($value, $key) {
                return $key . '-' . $value['name'];
            },
            function ($value, $key) {
                return $key . '-' . $value['id'];
            }
        );
        $this->assertEquals($expected, $collection->toArray());

        $collection = (new Collection($items))->combine('id', 'crazy');
        $this->assertEquals(array(1 => null, 2 => null, 3 => null), $collection->toArray());
    }

    /**
     * Tests the nest method with only one level
     *
     * @return void
     */
    public function testNest()
    {
        $items = array(
            array('id' => 1, 'parent_id' => null),
            array('id' => 2, 'parent_id' => 1),
            array('id' => 3, 'parent_id' => 1),
            array('id' => 4, 'parent_id' => 1),
            array('id' => 5, 'parent_id' => 6),
            array('id' => 6, 'parent_id' => null),
            array('id' => 7, 'parent_id' => 1),
            array('id' => 8, 'parent_id' => 6),
            array('id' => 9, 'parent_id' => 6),
            array('id' => 10, 'parent_id' => 6)
        );
        $collection = (new Collection($items))->nest('id', 'parent_id');
        $expected = array(
            array(
                'id' => 1,
                'parent_id' => null,
                'children' => array(
                    array('id' => 2, 'parent_id' => 1, 'children' => array()),
                    array('id' => 3, 'parent_id' => 1, 'children' => array()),
                    array('id' => 4, 'parent_id' => 1, 'children' => array()),
                    array('id' => 7, 'parent_id' => 1, 'children' => array())
                )
            ),
            array(
                'id' => 6,
                'parent_id' => null,
                'children' => array(
                    array('id' => 5, 'parent_id' => 6, 'children' => array()),
                    array('id' => 8, 'parent_id' => 6, 'children' => array()),
                    array('id' => 9, 'parent_id' => 6, 'children' => array()),
                    array('id' => 10, 'parent_id' => 6, 'children' => array())
                )
            )
        );
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with alternate nesting key
     *
     * @return void
     */
    public function testNestAlternateNestingKey()
    {
        $items = array(
            array('id' => 1, 'parent_id' => null),
            array('id' => 2, 'parent_id' => 1),
            array('id' => 3, 'parent_id' => 1),
            array('id' => 4, 'parent_id' => 1),
            array('id' => 5, 'parent_id' => 6),
            array('id' => 6, 'parent_id' => null),
            array('id' => 7, 'parent_id' => 1),
            array('id' => 8, 'parent_id' => 6),
            array('id' => 9, 'parent_id' => 6),
            array('id' => 10, 'parent_id' => 6)
        );
        $collection = (new Collection($items))->nest('id', 'parent_id', 'nodes');
        $expected = array(
            array(
                'id' => 1,
                'parent_id' => null,
                'nodes' => array(
                    array('id' => 2, 'parent_id' => 1, 'nodes' => array()),
                    array('id' => 3, 'parent_id' => 1, 'nodes' => array()),
                    array('id' => 4, 'parent_id' => 1, 'nodes' => array()),
                    array('id' => 7, 'parent_id' => 1, 'nodes' => array())
                )
            ),
            array(
                'id' => 6,
                'parent_id' => null,
                'nodes' => array(
                    array('id' => 5, 'parent_id' => 6, 'nodes' => array()),
                    array('id' => 8, 'parent_id' => 6, 'nodes' => array()),
                    array('id' => 9, 'parent_id' => 6, 'nodes' => array()),
                    array('id' => 10, 'parent_id' => 6, 'nodes' => array())
                )
            )
        );
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestMultiLevel()
    {
        $items = array(
            array('id' => 1, 'parent_id' => null),
            array('id' => 2, 'parent_id' => 1),
            array('id' => 3, 'parent_id' => 2),
            array('id' => 4, 'parent_id' => 2),
            array('id' => 5, 'parent_id' => 3),
            array('id' => 6, 'parent_id' => null),
            array('id' => 7, 'parent_id' => 3),
            array('id' => 8, 'parent_id' => 4),
            array('id' => 9, 'parent_id' => 6),
            array('id' => 10, 'parent_id' => 6)
        );
        $collection = (new Collection($items))->nest('id', 'parent_id', 'nodes');
        $expected = array(
            array(
                'id' => 1,
                'parent_id' => null,
                'nodes' => array(
                    array(
                        'id' => 2,
                        'parent_id' => 1,
                        'nodes' => array(
                            array(
                                'id' => 3,
                                'parent_id' => 2,
                                'nodes' => array(
                                    array('id' => 5, 'parent_id' => 3, 'nodes' => array()),
                                    array('id' => 7, 'parent_id' => 3, 'nodes' => array())
                                )
                            ),
                            array(
                                'id' => 4,
                                'parent_id' => 2,
                                'nodes' => array(
                                    array('id' => 8, 'parent_id' => 4, 'nodes' => array())
                                )
                            )
                        )
                    )
                )
            ),
            array(
                'id' => 6,
                'parent_id' => null,
                'nodes' => array(
                    array('id' => 9, 'parent_id' => 6, 'nodes' => array()),
                    array('id' => 10, 'parent_id' => 6, 'nodes' => array())
                )
            )
        );
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestMultiLevelAlternateNestingKey()
    {
        $items = array(
            array('id' => 1, 'parent_id' => null),
            array('id' => 2, 'parent_id' => 1),
            array('id' => 3, 'parent_id' => 2),
            array('id' => 4, 'parent_id' => 2),
            array('id' => 5, 'parent_id' => 3),
            array('id' => 6, 'parent_id' => null),
            array('id' => 7, 'parent_id' => 3),
            array('id' => 8, 'parent_id' => 4),
            array('id' => 9, 'parent_id' => 6),
            array('id' => 10, 'parent_id' => 6)
        );
        $collection = (new Collection($items))->nest('id', 'parent_id');
        $expected = array(
            array(
                'id' => 1,
                'parent_id' => null,
                'children' => array(
                    array(
                        'id' => 2,
                        'parent_id' => 1,
                        'children' => array(
                            array(
                                'id' => 3,
                                'parent_id' => 2,
                                'children' => array(
                                    array('id' => 5, 'parent_id' => 3, 'children' => array()),
                                    array('id' => 7, 'parent_id' => 3, 'children' => array())
                                )
                            ),
                            array(
                                'id' => 4,
                                'parent_id' => 2,
                                'children' => array(
                                    array('id' => 8, 'parent_id' => 4, 'children' => array())
                                )
                            )
                        )
                    )
                )
            ),
            array(
                'id' => 6,
                'parent_id' => null,
                'children' => array(
                    array('id' => 9, 'parent_id' => 6, 'children' => array()),
                    array('id' => 10, 'parent_id' => 6, 'children' => array())
                )
            )
        );
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestObjects()
    {
        $items = array(
            new ArrayObject(array('id' => 1, 'parent_id' => null)),
            new ArrayObject(array('id' => 2, 'parent_id' => 1)),
            new ArrayObject(array('id' => 3, 'parent_id' => 2)),
            new ArrayObject(array('id' => 4, 'parent_id' => 2)),
            new ArrayObject(array('id' => 5, 'parent_id' => 3)),
            new ArrayObject(array('id' => 6, 'parent_id' => null)),
            new ArrayObject(array('id' => 7, 'parent_id' => 3)),
            new ArrayObject(array('id' => 8, 'parent_id' => 4)),
            new ArrayObject(array('id' => 9, 'parent_id' => 6)),
            new ArrayObject(array('id' => 10, 'parent_id' => 6))
        );
        $collection = (new Collection($items))->nest('id', 'parent_id');
        $expected = array(
            new ArrayObject(array(
                'id' => 1,
                'parent_id' => null,
                'children' => array(
                    new ArrayObject(array(
                        'id' => 2,
                        'parent_id' => 1,
                        'children' => array(
                            new ArrayObject(array(
                                'id' => 3,
                                'parent_id' => 2,
                                'children' => array(
                                    new ArrayObject(array('id' => 5, 'parent_id' => 3, 'children' => array())),
                                    new ArrayObject(array('id' => 7, 'parent_id' => 3, 'children' => array()))
                                )
                            )),
                            new ArrayObject(array(
                                'id' => 4,
                                'parent_id' => 2,
                                'children' => array(
                                    new ArrayObject(array('id' => 8, 'parent_id' => 4, 'children' => array()))
                                )
                            ))
                        )
                    ))
                )
            )),
            new ArrayObject(array(
                'id' => 6,
                'parent_id' => null,
                'children' => array(
                    new ArrayObject(array('id' => 9, 'parent_id' => 6, 'children' => array())),
                    new ArrayObject(array('id' => 10, 'parent_id' => 6, 'children' => array()))
                )
            ))
        );
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestObjectsAlternateNestingKey()
    {
        $items = array(
            new ArrayObject(array('id' => 1, 'parent_id' => null)),
            new ArrayObject(array('id' => 2, 'parent_id' => 1)),
            new ArrayObject(array('id' => 3, 'parent_id' => 2)),
            new ArrayObject(array('id' => 4, 'parent_id' => 2)),
            new ArrayObject(array('id' => 5, 'parent_id' => 3)),
            new ArrayObject(array('id' => 6, 'parent_id' => null)),
            new ArrayObject(array('id' => 7, 'parent_id' => 3)),
            new ArrayObject(array('id' => 8, 'parent_id' => 4)),
            new ArrayObject(array('id' => 9, 'parent_id' => 6)),
            new ArrayObject(array('id' => 10, 'parent_id' => 6))
        );
        $collection = (new Collection($items))->nest('id', 'parent_id', 'nodes');
        $expected = array(
            new ArrayObject(array(
                'id' => 1,
                'parent_id' => null,
                'nodes' => array(
                    new ArrayObject(array(
                        'id' => 2,
                        'parent_id' => 1,
                        'nodes' => array(
                            new ArrayObject(array(
                                'id' => 3,
                                'parent_id' => 2,
                                'nodes' => array(
                                    new ArrayObject(array('id' => 5, 'parent_id' => 3, 'nodes' => array())),
                                    new ArrayObject(array('id' => 7, 'parent_id' => 3, 'nodes' => array()))
                                )
                            )),
                            new ArrayObject(array(
                                'id' => 4,
                                'parent_id' => 2,
                                'nodes' => array(
                                    new ArrayObject(array('id' => 8, 'parent_id' => 4, 'nodes' => array()))
                                )
                            ))
                        )
                    ))
                )
            )),
            new ArrayObject(array(
                'id' => 6,
                'parent_id' => null,
                'nodes' => array(
                    new ArrayObject(array('id' => 9, 'parent_id' => 6, 'nodes' => array())),
                    new ArrayObject(array('id' => 10, 'parent_id' => 6, 'nodes' => array()))
                )
            ))
        );
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests insert
     *
     * @return void
     */
    public function testInsert()
    {
        $items = array(array('a' => 1), array('b' => 2));
        $collection = new Collection($items);
        $iterator = $collection->insert('c', array(3, 4));
        $this->assertInstanceOf('Cake\Collection\Iterator\InsertIterator', $iterator);
        $this->assertEquals(
            array(array('a' => 1, 'c' => 3), array('b' => 2, 'c' => 4)),
            iterator_to_array($iterator)
        );
    }

    /**
     * Provider for testing each of the directions for listNested
     *
     * @return void
     */
    public function nestedListProvider()
    {
        return array(
            array('desc', array(1, 2, 3, 5, 7, 4, 8, 6, 9, 10)),
            array('asc', array(5, 7, 3, 8, 4, 2, 1, 9, 10, 6)),
            array('leaves', array(5, 7, 8, 9, 10))
        );
    }

    /**
     * Tests the sumOf method
     *
     * @return void
     */
    public function testSumOf()
    {
        $items = array(
            array('invoice' => array('total' => 100)),
            array('invoice' => array('total' => 200))
        );
        $this->assertEquals(300, (new Collection($items))->sumOf('invoice.total'));

        $sum = (new Collection($items))->sumOf(function ($v) {
            return $v['invoice']['total'] * 2;
        });
        $this->assertEquals(600, $sum);
    }

    /**
     * Tests the stopWhen method with a callable
     *
     * @return void
     */
    public function testStopWhenCallable()
    {
        $items = array(10, 20, 40, 10, 5);
        $collection = (new Collection($items))->stopWhen(function ($v) {
            return $v > 20;
        });
        $this->assertEquals(array(10, 20), $collection->toArray());
    }

    /**
     * Tests the stopWhen method with a matching array
     *
     * @return void
     */
    public function testStopWhenWithArray()
    {
        $items = array(
            array('foo' => 'bar'),
            array('foo' => 'baz'),
            array('foo' => 'foo')
        );
        $collection = (new Collection($items))->stopWhen(array('foo' => 'baz'));
        $this->assertEquals(array(array('foo' => 'bar')), $collection->toArray());
    }

    /**
     * Tests the unfold method
     *
     * @return void
     */
    public function testUnfold()
    {
        $items = array(
            array(1, 2, 3, 4),
            array(5, 6),
            array(7, 8)
        );

        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 8), $collection->toArray(false));

        $items = array(
            array(1, 2),
            new Collection(array(3, 4))
        );
        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 4), $collection->toArray(false));
    }

    /**
     * Tests the unfold method with empty levels
     *
     * @return void
     */
    public function testUnfoldEmptyLevels()
    {
        $items = array(array(), array(1, 2), array());
        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 2), $collection->toArray(false));

        $items = array();
        $collection = (new Collection($items))->unfold();
        $this->assertEmpty($collection->toArray(false));
    }

    /**
     * Tests the unfold when passing a callable
     *
     * @return void
     */
    public function testUnfoldWithCallable()
    {
        $items = array(1, 2, 3);
        $collection = (new Collection($items))->unfold(function ($item) {
            return range($item, $item * 2);
        });
        $expected = array(1, 2, 2, 3, 4, 3, 4, 5, 6);
        $this->assertEquals($expected, $collection->toArray(false));
    }

    /**
     * Tests the through() method
     *
     * @return void
     */
    public function testThrough()
    {
        $items = array(1, 2, 3);
        $collection = (new Collection($items))->through(function ($collection) {
            return $collection->append($collection->toList());
        });

        $this->assertEquals(array(1, 2, 3, 1, 2, 3), $collection->toList());
    }

    /**
     * Tests the through method when it returns an array
     *
     * @return void
     */
    public function testThroughReturnArray()
    {
        $items = array(1, 2, 3);
        $collection = (new Collection($items))->through(function ($collection) {
            $list = $collection->toList();

            return array_merge($list, $list);
        });

        $this->assertEquals(array(1, 2, 3, 1, 2, 3), $collection->toList());
    }

    /**
     * Tests that the sortBy method does not die when something that is not a
     * collection is passed
     *
     * @return void
     */
    public function testComplexSortBy()
    {
        $results = collection(array(3, 7))
            ->unfold(function ($value) {
                return array(
                    array('sorting' => $value * 2),
                    array('sorting' => $value * 2)
                );
            })
            ->sortBy('sorting')
            ->extract('sorting')
            ->toList();
        $this->assertEquals(array(14, 14, 6, 6), $results);
    }

    /**
     * Tests __debugInfo() or debug() usage
     *
     * @return void
     */
    public function testDebug()
    {
        $items = array(1, 2, 3);

        $collection = new Collection($items);

        $result = $collection->__debugInfo();
        $expected = array(
            'count' => 3,
        );
        $this->assertSame($expected, $result);

        // Calling it again will rewind
        $result = $collection->__debugInfo();
        $expected = array(
            'count' => 3,
        );
        $this->assertSame($expected, $result);

        // Make sure it also works with non rewindable iterators
        $iterator = new NoRewindIterator(new ArrayIterator($items));
        $collection = new Collection($iterator);

        $result = $collection->__debugInfo();
        $expected = array(
            'count' => 3,
        );
        $this->assertSame($expected, $result);

        // Calling it again will in this case not rewind
        $result = $collection->__debugInfo();
        $expected = array(
            'count' => 0,
        );
        $this->assertSame($expected, $result);
    }

    /**
     * Tests the isEmpty() method
     *
     * @return void
     */
    public function testIsEmpty()
    {
        $collection = new Collection(array(1, 2, 3));
        $this->assertFalse($collection->isEmpty());

        $collection = $collection->map(function () {
            return null;
        });
        $this->assertFalse($collection->isEmpty());

        $collection = $collection->filter();
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * Tests the isEmpty() method does not consume data
     * from buffered iterators.
     *
     * @return void
     */
    public function testIsEmptyDoesNotConsume()
    {
        $array = new \ArrayIterator(array(1, 2, 3));
        $inner = new \Cake\Collection\Iterator\BufferedIterator($array);
        $collection = new Collection($inner);
        $this->assertFalse($collection->isEmpty());
        $this->assertCount(3, $collection->toArray());
    }

    /**
     * Tests the skip() method
     *
     * @return void
     */
    public function testSkip()
    {
        $collection = new Collection(array(1, 2, 3, 4, 5));
        $this->assertEquals(array(3, 4, 5), $collection->skip(2)->toList());

        $this->assertEquals(array(5), $collection->skip(4)->toList());
    }

    /**
     * Tests the last() method
     *
     * @return void
     */
    public function testLast()
    {
        $collection = new Collection(array(1, 2, 3));
        $this->assertEquals(3, $collection->last());

        $collection = $collection->map(function ($e) {
            return $e * 2;
        });
        $this->assertEquals(6, $collection->last());
    }

    /**
     * Tests the last() method when on an empty collection
     *
     * @return void
     */
    public function testLAstWithEmptyCollection()
    {
        $collection = new Collection(array());
        $this->assertNull($collection->last());
    }

    /**
     * Tests sumOf with no parameters
     *
     * @return void
     */
    public function testSumOfWithIdentity()
    {
        $collection = new Collection(array(1, 2, 3));
        $this->assertEquals(6, $collection->sumOf());

        $collection = new Collection(array('a' => 1, 'b' => 4, 'c' => 6));
        $this->assertEquals(11, $collection->sumOf());
    }

    /**
     * Tests using extract with the {*} notation
     *
     * @return void
     */
    public function testUnfoldedExtract()
    {
        $items = array(
            array('comments' => array(array('id' => 1), array('id' => 2))),
            array('comments' => array(array('id' => 3), array('id' => 4))),
            array('comments' => array(array('id' => 7), array('nope' => 8))),
        );

        $extracted = (new Collection($items))->extract('comments.{*}.id');
        $this->assertEquals(array(1, 2, 3, 4, 7, null), $extracted->toArray());

        $items = array(
            array(
                'comments' => array(
                    array(
                        'voters' => array(array('id' => 1), array('id' => 2))
                    )
                )
            ),
            array(
                'comments' => array(
                    array(
                        'voters' => array(array('id' => 3), array('id' => 4))
                    )
                )
            ),
            array(
                'comments' => array(
                    array(
                        'voters' => array(array('id' => 5), array('nope' => 'fail'), array('id' => 6))
                    )
                )
            ),
            array(
                'comments' => array(
                    array(
                        'not_voters' => array(array('id' => 5))
                    )
                )
            ),
            array('not_comments' => array())
        );
        $extracted = (new Collection($items))->extract('comments.{*}.voters.{*}.id');
        $expected = array(1, 2, 3, 4, 5, null, 6);
        $this->assertEquals($expected, $extracted->toArray());
        $this->assertEquals($expected, $extracted->toList());
    }

    /**
     * Tests serializing a simple collection
     *
     * @return void
     */
    public function testSerializeSimpleCollection()
    {
        $collection = new Collection(array(1, 2, 3));
        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
        $this->assertEquals($collection->toArray(), $unserialized->toArray());
    }

    /**
     * Tests serialization when using append
     *
     * @return void
     */
    public function testSerializeWithAppendIterators()
    {
        $collection = new Collection(array(1, 2, 3));
        $collection = $collection->append(array('a' => 4, 'b' => 5, 'c' => 6));
        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
        $this->assertEquals($collection->toArray(), $unserialized->toArray());
    }

    /**
     * Tests serialization when using nested iterators
     *
     * @return void
     */
    public function testSerializeWithNestedIterators()
    {
        $collection = new Collection(array(1, 2, 3));
        $collection = $collection->map(function ($e) {
            return $e * 3;
        });

        $collection = $collection->groupBy(function ($e) {
            return $e % 2;
        });

        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
        $this->assertEquals($collection->toArray(), $unserialized->toArray());
    }

    /**
     * Tests the chunk method with exact chunks
     *
     * @return void
     */
    public function testChunk()
    {
        $collection = new Collection(range(1, 10));
        $chunked = $collection->chunk(2)->toList();
        $expected = array(array(1, 2), array(3, 4), array(5, 6), array(7, 8), array(9, 10));
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunk method with overflowing chunk size
     *
     * @return void
     */
    public function testChunkOverflow()
    {
        $collection = new Collection(range(1, 11));
        $chunked = $collection->chunk(2)->toList();
        $expected = array(array(1, 2), array(3, 4), array(5, 6), array(7, 8), array(9, 10), array(11));
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunk method with non-scalar items
     *
     * @return void
     */
    public function testChunkNested()
    {
        $collection = new Collection(array(1, 2, 3, array(4, 5), 6, array(7, array(8, 9), 10), 11));
        $chunked = $collection->chunk(2)->toList();
        $expected = array(array(1, 2), array(3, array(4, 5)), array(6, array(7, array(8, 9), 10)), array(11));
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method with exact chunks
     *
     * @return void
     */
    public function testChunkWithKeys()
    {
        $collection = new Collection(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6));
        $chunked = $collection->chunkWithKeys(2)->toList();
        $expected = array(array('a' => 1, 'b' => 2), array('c' => 3, 'd' => 4), array('e' => 5, 'f' => 6));
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method with overflowing chunk size
     *
     * @return void
     */
    public function testChunkWithKeysOverflow()
    {
        $collection = new Collection(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7));
        $chunked = $collection->chunkWithKeys(2)->toList();
        $expected = array(array('a' => 1, 'b' => 2), array('c' => 3, 'd' => 4), array('e' => 5, 'f' => 6), array('g' => 7));
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method with non-scalar items
     *
     * @return void
     */
    public function testChunkWithKeysNested()
    {
        $collection = new Collection(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => array(4, 5), 'e' => 6, 'f' => array(7, array(8, 9), 10), 'g' => 11));
        $chunked = $collection->chunkWithKeys(2)->toList();
        $expected = array(array('a' => 1, 'b' => 2), array('c' => 3, 'd' => array(4, 5)), array('e' => 6, 'f' => array(7, array(8, 9), 10)), array('g' => 11));
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method without preserving keys
     *
     * @return void
     */
    public function testChunkWithKeysNoPreserveKeys()
    {
        $collection = new Collection(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7));
        $chunked = $collection->chunkWithKeys(2, false)->toList();
        $expected = array(array(0 => 1, 1 => 2), array(0 => 3, 1 => 4), array(0 => 5, 1 => 6), array(0 => 7));
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests cartesianProduct
     *
     * @return void
     */
    public function testCartesianProduct()
    {
        $collection = new Collection(array());

        $result = $collection->cartesianProduct();

        $expected = array();

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection(array(array('A', 'B', 'C'), array(1, 2, 3)));

        $result = $collection->cartesianProduct();

        $expected = array(
            array('A', 1),
            array('A', 2),
            array('A', 3),
            array('B', 1),
            array('B', 2),
            array('B', 3),
            array('C', 1),
            array('C', 2),
            array('C', 3),
        );

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection(array(array(1, 2, 3), array('A', 'B', 'C'), array('a', 'b', 'c')));

        $result = $collection->cartesianProduct(function ($value) {
            return array(strval($value[0]) . $value[1] . $value[2]);
        }, function ($value) {
            return $value[0] >= 2;
        });

        $expected = array(
            array('2Aa'),
            array('2Ab'),
            array('2Ac'),
            array('2Ba'),
            array('2Bb'),
            array('2Bc'),
            array('2Ca'),
            array('2Cb'),
            array('2Cc'),
            array('3Aa'),
            array('3Ab'),
            array('3Ac'),
            array('3Ba'),
            array('3Bb'),
            array('3Bc'),
            array('3Ca'),
            array('3Cb'),
            array('3Cc'),
        );

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection(array(array('1', '2', '3', '4'), array('A', 'B', 'C'), array('name', 'surname', 'telephone')));

        $result = $collection->cartesianProduct(function ($value) {
            return array($value[0] => array($value[1] => $value[2]));
        }, function ($value) {
            return $value[2] !== 'surname';
        });

        $expected = array(
            array(1 => array('A' => 'name')),
            array(1 => array('A' => 'telephone')),
            array(1 => array('B' => 'name')),
            array(1 => array('B' => 'telephone')),
            array(1 => array('C' => 'name')),
            array(1 => array('C' => 'telephone')),
            array(2 => array('A' => 'name')),
            array(2 => array('A' => 'telephone')),
            array(2 => array('B' => 'name')),
            array(2 => array('B' => 'telephone')),
            array(2 => array('C' => 'name')),
            array(2 => array('C' => 'telephone')),
            array(3 => array('A' => 'name')),
            array(3 => array('A' => 'telephone')),
            array(3 => array('B' => 'name')),
            array(3 => array('B' => 'telephone')),
            array(3 => array('C' => 'name')),
            array(3 => array('C' => 'telephone')),
            array(4 => array('A' => 'name')),
            array(4 => array('A' => 'telephone')),
            array(4 => array('B' => 'name')),
            array(4 => array('B' => 'telephone')),
            array(4 => array('C' => 'name')),
            array(4 => array('C' => 'telephone')),
        );

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection(array(
            array(
                'name1' => 'alex',
                'name2' => 'kostas',
                0 => 'leon',
            ),
            array(
                'val1' => 'alex@example.com',
                24 => 'kostas@example.com',
                'val2' => 'leon@example.com',
            ),
        ));

        $result = $collection->cartesianProduct();

        $expected = array(
            array('alex', 'alex@example.com'),
            array('alex', 'kostas@example.com'),
            array('alex', 'leon@example.com'),
            array('kostas', 'alex@example.com'),
            array('kostas', 'kostas@example.com'),
            array('kostas', 'leon@example.com'),
            array('leon', 'alex@example.com'),
            array('leon', 'kostas@example.com'),
            array('leon', 'leon@example.com'),
        );

        $this->assertEquals($expected, $result->toList());
    }

    /**
     * Tests that an exception is thrown if the cartesian product is called with multidimensional arrays
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testCartesianProductMultidimensionalArray()
    {
        $collection = new Collection(array(
            array(
                'names' => array(
                    'alex', 'kostas', 'leon'
                )
            ),
            array(
                'locations' => array(
                    'crete', 'london', 'paris'
                )
            ),
        ));

        $result = $collection->cartesianProduct();
    }

    public function testTranspose()
    {
        $collection = new Collection(array(
            array('Products', '2012', '2013', '2014'),
            array('Product A', '200', '100', '50'),
            array('Product B', '300', '200', '100'),
            array('Product C', '400', '300', '200'),
            array('Product D', '500', '400', '300'),
        ));
        $transposed = $collection->transpose();
        $expected = array(
            array('Products', 'Product A', 'Product B', 'Product C', 'Product D'),
            array('2012', '200', '300', '400', '500'),
            array('2013', '100', '200', '300', '400'),
            array('2014', '50', '100', '200', '300'),
        );

        $this->assertEquals($expected, $transposed->toList());
    }

    /**
     * Tests that provided arrays do not have even length
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testTransposeUnEvenLengthShouldThrowException()
    {
        $collection = new Collection(array(
            array('Products', '2012', '2013', '2014'),
            array('Product A', '200', '100', '50'),
            array('Product B', '300'),
            array('Product C', '400', '300'),
        ));

        $collection->transpose();
    }
}
