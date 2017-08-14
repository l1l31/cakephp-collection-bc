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
namespace Cake\Test\TestCase\Collection\Iterator;

use Cake\Collection\Iterator\InsertIterator;
use PHPUnit\Framework\TestCase;

/**
 * InsertIterator Test
 */
class InsertIteratorTest extends TestCase
{

    /**
     * Test insert simple path
     *
     * @return void
     */
    public function testInsertSimplePath()
    {
        $items = array(
            'a' => array('name' => 'Derp'),
            'b' => array('name' => 'Derpina')
        );
        $values = array(20, 21);
        $iterator = new InsertIterator($items, 'age', $values);
        $result = $iterator->toArray();
        $expected = array(
            'a' => array('name' => 'Derp', 'age' => 20),
            'b' => array('name' => 'Derpina', 'age' => 21)
        );
        $this->assertSame($expected, $result);
    }

    /**
     * Test insert deep path
     *
     * @return void
     */
    public function testInsertDeepPath()
    {
        $items = array(
            'a' => array('name' => 'Derp', 'a' => array('deep' => array('thing' => 1))),
            'b' => array('name' => 'Derpina', 'a' => array('deep' => array('thing' => 2))),
        );
        $values = new \ArrayIterator(array(20, 21));
        $iterator = new InsertIterator($items, 'a.deep.path', $values);
        $result = $iterator->toArray();
        $expected = array(
            'a' => array('name' => 'Derp', 'a' => array('deep' => array('thing' => 1, 'path' => 20))),
            'b' => array('name' => 'Derpina', 'a' => array('deep' => array('thing' => 2, 'path' => 21))),
        );
        $this->assertSame($expected, $result);
    }

    /**
     * Test that missing properties in the path will skip inserting
     *
     * @return void
     */
    public function testInsertDeepPathMissingStep()
    {
        $items = array(
            'a' => array('name' => 'Derp', 'a' => array('deep' => array('thing' => 1))),
            'b' => array('name' => 'Derpina', 'a' => array('nested' => 2)),
        );
        $values = array(20, 21);
        $iterator = new InsertIterator($items, 'a.deep.path', $values);
        $result = $iterator->toArray();
        $expected = array(
            'a' => array('name' => 'Derp', 'a' => array('deep' => array('thing' => 1, 'path' => 20))),
            'b' => array('name' => 'Derpina', 'a' => array('nested' => 2)),
        );
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that the iterator will insert values as long as there still exist
     * some in the values array
     *
     * @return void
     */
    public function testInsertTargetCountBigger()
    {
        $items = array(
            'a' => array('name' => 'Derp'),
            'b' => array('name' => 'Derpina')
        );
        $values = array(20);
        $iterator = new InsertIterator($items, 'age', $values);
        $result = $iterator->toArray();
        $expected = array(
            'a' => array('name' => 'Derp', 'age' => 20),
            'b' => array('name' => 'Derpina')
        );
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that the iterator will insert values as long as there still exist
     * some in the values array
     *
     * @return void
     */
    public function testInsertSourceBigger()
    {
        $items = array(
            'a' => array('name' => 'Derp'),
            'b' => array('name' => 'Derpina')
        );
        $values = array(20, 21, 23);
        $iterator = new InsertIterator($items, 'age', $values);
        $result = $iterator->toArray();
        $expected = array(
            'a' => array('name' => 'Derp', 'age' => 20),
            'b' => array('name' => 'Derpina', 'age' => 21)
        );
        $this->assertSame($expected, $result);
    }

    /**
     * Tests the iterator can be rewound
     *
     * @return void
     */
    public function testRewind()
    {
        $items = array(
            'a' => array('name' => 'Derp'),
            'b' => array('name' => 'Derpina'),
        );
        $values = array(20, 21);
        $iterator = new InsertIterator($items, 'age', $values);
        $iterator->next();
        $this->assertEquals(array('name' => 'Derpina', 'age' => 21), $iterator->current());
        $iterator->rewind();

        $result = $iterator->toArray();
        $expected = array(
            'a' => array('name' => 'Derp', 'age' => 20),
            'b' => array('name' => 'Derpina', 'age' => 21)
        );
        $this->assertSame($expected, $result);
    }
}
