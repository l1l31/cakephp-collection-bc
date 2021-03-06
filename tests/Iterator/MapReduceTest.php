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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Collection\Iterator;

use ArrayIterator;
use Cake\Collection\Iterator\MapReduce;
use PHPUnit\Framework\TestCase;

/**
 * Tests MapReduce class
 */
class MapReduceTest extends TestCase
{

    /**
     * Tests the creation of an inversed index of words to documents using
     * MapReduce
     *
     * @return void
     */
    public function testInvertedIndexCreation()
    {
        $data = array(
            'document_1' => 'Dogs are the most amazing animal in history',
            'document_2' => 'History is not only amazing but boring',
            'document_3' => 'One thing that is not boring is dogs'
        );
        $mapper = function ($row, $document, $mr) {
            $words = array_map('strtolower', explode(' ', $row));
            foreach ($words as $word) {
                $mr->emitIntermediate($document, $word);
            }
        };
        $reducer = function ($documents, $word, $mr) {
            $mr->emit(array_unique($documents), $word);
        };
        $results = new MapReduce(new ArrayIterator($data), $mapper, $reducer);
        $expected = array(
            'dogs' => array('document_1', 'document_3'),
            'are' => array('document_1'),
            'the' => array('document_1'),
            'most' => array('document_1'),
            'amazing' => array('document_1', 'document_2'),
            'animal' => array('document_1'),
            'in' => array('document_1'),
            'history' => array('document_1', 'document_2'),
            'is' => array('document_2', 'document_3'),
            'not' => array('document_2', 'document_3'),
            'only' => array('document_2'),
            'but' => array('document_2'),
            'boring' => array('document_2', 'document_3'),
            'one' => array('document_3'),
            'thing' => array('document_3'),
            'that' => array('document_3')
        );
        $this->assertEquals($expected, iterator_to_array($results));
    }

    /**
     * Tests that it is possible to use the emit function directly in the mapper
     *
     * @return void
     */
    public function testEmitFinalInMapper()
    {
        $data = array('a' => array('one', 'two'), 'b' => array('three', 'four'));
        $mapper = function ($row, $key, $mr) {
            foreach ($row as $number) {
                $mr->emit($number);
            }
        };
        $results = new MapReduce(new ArrayIterator($data), $mapper);
        $expected = array('one', 'two', 'three', 'four');
        $this->assertEquals($expected, iterator_to_array($results));
    }

    /**
     * Tests that a reducer is required when there are intermediate results
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testReducerRequired()
    {
        $data = array('a' => array('one', 'two'), 'b' => array('three', 'four'));
        $mapper = function ($row, $key, $mr) {
            foreach ($row as $number) {
                $mr->emitIntermediate('a', $number);
            }
        };
        $results = new MapReduce(new ArrayIterator($data), $mapper);
        iterator_to_array($results);
    }
}
