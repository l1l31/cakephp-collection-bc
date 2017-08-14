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
namespace Cake\Collection;

use ArrayIterator;
use InvalidArgumentException;
use IteratorIterator;
use LogicException;
use Serializable;
use Traversable;
use AppendIterator;
use Cake\Collection\Iterator\BufferedIterator;
use Cake\Collection\Iterator\ExtractIterator;
use Cake\Collection\Iterator\FilterIterator;
use Cake\Collection\Iterator\InsertIterator;
use Cake\Collection\Iterator\MapReduce;
use Cake\Collection\Iterator\NestIterator;
use Cake\Collection\Iterator\ReplaceIterator;
use Cake\Collection\Iterator\SortIterator;
use Cake\Collection\Iterator\StoppableIterator;
use Cake\Collection\Iterator\UnfoldIterator;
use Cake\Collection\Iterator\ZipIterator;
use Countable;
use LimitIterator;
use RecursiveIteratorIterator;

/**
 * A collection is an immutable list of elements with a handful of functions to
 * iterate, group, transform and extract information from it.
 */
class Collection extends IteratorIterator implements CollectionInterface, Serializable
{
    /**
     * Constructor. You can provide an array or any traversable object
     *
     * @param array|\Traversable $items Items.
     * @throws \InvalidArgumentException If passed incorrect type for items.
     */
    public function __construct($items)
    {
        if (is_array($items)) {
            $items = new ArrayIterator($items);
        }

        if (!($items instanceof Traversable)) {
            $msg = 'Only an array or \Traversable is allowed for Collection';
            throw new InvalidArgumentException($msg);
        }

        parent::__construct($items);
    }

    /**
     * Returns a string representation of this object that can be used
     * to reconstruct it
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->buffered());
    }

    /**
     * Unserializes the passed string and rebuilds the Collection instance
     *
     * @param string $collection The serialized collection
     * @return void
     */
    public function unserialize($collection)
    {
        $this->__construct(unserialize($collection));
    }

    /**
     * Throws an exception.
     *
     * Issuing a count on a Collection can have many side effects, some making the
     * Collection unusable after the count operation.
     *
     * @return void
     * @throws \LogicException
     */
    public function count()
    {
        throw new LogicException('You cannot issue a count on a Collection.');
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return array(
            'count' => iterator_count($this),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function each(callable $c)
    {
        foreach ($this->unwrap() as $k => $v) {
            $c($v, $k);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\Iterator\FilterIterator
     */
    public function filter(callable $c = null)
    {
        if ($c === null) {
            $c = function ($v) {
                return (bool)$v;
            };
        }

        return new FilterIterator($this->unwrap(), $c);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\Iterator\FilterIterator
     */
    public function reject(callable $c)
    {
        return new FilterIterator($this->unwrap(), function ($key, $value, $items) use ($c) {
            return !$c($key, $value, $items);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function every(callable $c)
    {
        foreach ($this->unwrap() as $key => $value) {
            if (!$c($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function some(callable $c)
    {
        foreach ($this->unwrap() as $key => $value) {
            if ($c($value, $key) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function contains($value)
    {
        foreach ($this->unwrap() as $v) {
            if ($value === $v) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\Iterator\ReplaceIterator
     */
    public function map(callable $c)
    {
        return new ReplaceIterator($this->unwrap(), $c);
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(callable $c, $zero = null)
    {
        $isFirst = false;
        if (func_num_args() < 2) {
            $isFirst = true;
        }

        $result = $zero;
        foreach ($this->unwrap() as $k => $value) {
            if ($isFirst) {
                $result = $value;
                $isFirst = false;
                continue;
            }
            $result = $c($result, $value, $k);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function extract($matcher)
    {
        $extractor = new ExtractIterator($this->unwrap(), $matcher);
        if (is_string($matcher) && strpos($matcher, '{*}') !== false) {
            $extractor = $extractor
                ->filter(function ($data) {
                    return $data !== null && ($data instanceof Traversable || is_array($data));
                })
                ->unfold();
        }

        return $extractor;
    }

    /**
     * {@inheritDoc}
     */
    public function max($callback, $type = SORT_NUMERIC)
    {
        return (new SortIterator($this->unwrap(), $callback, SORT_DESC, $type))->first();
    }

    /**
     * {@inheritDoc}
     */
    public function min($callback, $type = SORT_NUMERIC)
    {
        return (new SortIterator($this->unwrap(), $callback, SORT_ASC, $type))->first();
    }

    /**
     * {@inheritDoc}
     */
    public function sortBy($callback, $dir = SORT_DESC, $type = SORT_NUMERIC)
    {
        return new SortIterator($this->unwrap(), $callback, $dir, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function groupBy($callback)
    {
        $callback = $this->_propertyExtractor($callback);
        $group = array();
        foreach ($this as $value) {
            $group[$callback($value)][] = $value;
        }

        return new Collection($group);
    }

    /**
     * {@inheritDoc}
     */
    public function indexBy($callback)
    {
        $callback = $this->_propertyExtractor($callback);
        $group = array();
        foreach ($this as $value) {
            $group[$callback($value)] = $value;
        }

        return new Collection($group);
    }

    /**
     * {@inheritDoc}
     */
    public function countBy($callback)
    {
        $callback = $this->_propertyExtractor($callback);

        $mapper = function ($value, $key, $mr) use ($callback) {
            $mr->emitIntermediate($value, $callback($value));
        };

        $reducer = function ($values, $key, $mr) {
            $mr->emit(count($values), $key);
        };

        return new Collection(new MapReduce($this->unwrap(), $mapper, $reducer));
    }

    /**
     * {@inheritDoc}
     */
    public function sumOf($matcher = null)
    {
        if ($matcher === null) {
            return array_sum($this->toList());
        }

        $callback = $this->_propertyExtractor($matcher);
        $sum = 0;
        foreach ($this as $k => $v) {
            $sum += $callback($v, $k);
        }

        return $sum;
    }

    /**
     * {@inheritDoc}
     */
    public function shuffle()
    {
        $elements = $this->toArray();
        shuffle($elements);

        return new Collection($elements);
    }

    /**
     * {@inheritDoc}
     */
    public function sample($size = 10)
    {
        return new Collection(new LimitIterator($this->shuffle(), 0, $size));
    }

    /**
     * {@inheritDoc}
     */
    public function take($size = 1, $from = 0)
    {
        return new Collection(new LimitIterator($this->unwrap(), $from, $size));
    }

    /**
     * {@inheritDoc}
     */
    public function skip($howMany)
    {
        return new Collection(new LimitIterator($this->unwrap(), $howMany));
    }

    /**
     * {@inheritDoc}
     */
    public function match(array $conditions)
    {
        return $this->filter($this->_createMatcherFilter($conditions));
    }

    /**
     * {@inheritDoc}
     */
    public function firstMatch(array $conditions)
    {
        return $this->match($conditions)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function first()
    {
        foreach ($this->take(1) as $result) {
            return $result;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function last()
    {
        $iterator = $this->unwrap();
        $count = $iterator instanceof Countable ?
            count($iterator) :
            iterator_count($iterator);

        if ($count === 0) {
            return null;
        }

        foreach ($this->take(1, $count - 1) as $last) {
            return $last;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function append($items)
    {
        $list = new AppendIterator();
        $list->append($this->unwrap());
        $list->append((new Collection($items))->unwrap());

        return new Collection($list);
    }

    /**
     * {@inheritDoc}
     */
    public function combine($keyPath, $valuePath, $groupPath = null)
    {
        $options = array(
            'keyPath' => $this->_propertyExtractor($keyPath),
            'valuePath' => $this->_propertyExtractor($valuePath),
            'groupPath' => $groupPath ? $this->_propertyExtractor($groupPath) : null
        );

        $mapper = function ($value, $key, $mapReduce) use ($options) {
            $rowKey = $options['keyPath'];
            $rowVal = $options['valuePath'];

            if (!$options['groupPath']) {
                $mapReduce->emit($rowVal($value, $key), $rowKey($value, $key));

                return null;
            }

            $key = $options['groupPath']($value, $key);
            $mapReduce->emitIntermediate(
                array($rowKey($value, $key) => $rowVal($value, $key)),
                $key
            );
        };

        $reducer = function ($values, $key, $mapReduce) {
            $result = array();
            foreach ($values as $value) {
                $result += $value;
            }
            $mapReduce->emit($result, $key);
        };

        return new Collection(new MapReduce($this->unwrap(), $mapper, $reducer));
    }

    /**
     * {@inheritDoc}
     */
    public function nest($idPath, $parentPath, $nestingKey = 'children')
    {
        $parents = array();
        $idPath = $this->_propertyExtractor($idPath);
        $parentPath = $this->_propertyExtractor($parentPath);
        $isObject = true;

        $mapper = function ($row, $key, $mapReduce) use (&$parents, $idPath, $parentPath, $nestingKey) {
            $row[$nestingKey] = array();
            $id = $idPath($row, $key);
            $parentId = $parentPath($row, $key);
            $parents[$id] =& $row;
            $mapReduce->emitIntermediate($id, $parentId);
        };

        $reducer = function ($values, $key, $mapReduce) use (&$parents, &$isObject, $nestingKey) {
            static $foundOutType = false;
            if (!$foundOutType) {
                $isObject = is_object(current($parents));
                $foundOutType = true;
            }
            if (empty($key) || !isset($parents[$key])) {
                foreach ($values as $id) {
                    $parents[$id] = $isObject ? $parents[$id] : new ArrayIterator($parents[$id], 1);
                    $mapReduce->emit($parents[$id]);
                }

                return null;
            }

            $children = array();
            foreach ($values as $id) {
                $children[] =& $parents[$id];
            }
            $parents[$key][$nestingKey] = $children;
        };

        return (new Collection(new MapReduce($this->unwrap(), $mapper, $reducer)))
            ->map(function ($value) use (&$isObject) {
                return $isObject ? $value : $value->getArrayCopy();
            });
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\Iterator\InsertIterator
     */
    public function insert($path, $values)
    {
        return new InsertIterator($this->unwrap(), $path, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray($preserveKeys = true)
    {
        $iterator = $this->unwrap();
        if ($iterator instanceof ArrayIterator) {
            $items = $iterator->getArrayCopy();

            return $preserveKeys ? $items : array_values($items);
        }
        // RecursiveIteratorIterator can return duplicate key values causing
        // data loss when converted into an array
        if ($preserveKeys && get_class($iterator) === 'RecursiveIteratorIterator') {
            $preserveKeys = false;
        }

        return iterator_to_array($this, $preserveKeys);
    }

    /**
     * {@inheritDoc}
     */
    public function toList()
    {
        return $this->toArray(false);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function compile($preserveKeys = true)
    {
        return new Collection($this->toArray($preserveKeys));
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\Iterator\BufferedIterator
     */
    public function buffered()
    {
        return new BufferedIterator($this);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\Iterator\StoppableIterator
     */
    public function stopWhen($condition)
    {
        if (!is_callable($condition)) {
            $condition = $this->_createMatcherFilter($condition);
        }

        return new StoppableIterator($this, $condition);
    }

    /**
     * {@inheritDoc}
     */
    public function unfold(callable $transformer = null)
    {
        if ($transformer === null) {
            $transformer = function ($item) {
                return $item;
            };
        }

        return new Collection(
            new RecursiveIteratorIterator(
                new UnfoldIterator($this, $transformer),
                RecursiveIteratorIterator::LEAVES_ONLY
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function through(callable $handler)
    {
        $result = $handler($this);

        return $result instanceof CollectionInterface ? $result : new Collection($result);
    }

    /**
     * {@inheritDoc}
     */
    public function chunk($chunkSize)
    {
        return $this->map(function ($v, $k, $iterator) use ($chunkSize) {
            $values = array($v);
            for ($i = 1; $i < $chunkSize; $i++) {
                $iterator->next();
                if (!$iterator->valid()) {
                    break;
                }
                $values[] = $iterator->current();
            }

            return $values;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function chunkWithKeys($chunkSize, $preserveKeys = true)
    {
        return $this->map(function ($v, $k, $iterator) use ($chunkSize, $preserveKeys) {
            $key = 0;
            if ($preserveKeys) {
                $key = $k;
            }
            $values = array($key => $v);
            for ($i = 1; $i < $chunkSize; $i++) {
                $iterator->next();
                if (!$iterator->valid()) {
                    break;
                }
                if ($preserveKeys) {
                    $values[$iterator->key()] = $iterator->current();
                } else {
                    $values[] = $iterator->current();
                }
            }

            return $values;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        foreach ($this->unwrap() as $el) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function unwrap()
    {
        $iterator = $this;
        while (get_class($iterator) === 'Cake\Collection\Collection') {
            $iterator = $iterator->getInnerIterator();
        }

        return $iterator;
    }

    /**
     * Backwards compatible wrapper for unwrap()
     *
     * @return \Iterator
     * @deprecated
     */
    // @codingStandardsIgnoreLine
    public function _unwrap()
    {
        return $this->unwrap();
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\CollectionInterface
     */
    public function cartesianProduct(callable $operation = null, callable $filter = null)
    {
        if ($this->isEmpty()) {
            return new Collection(array());
        }

        $collectionArrays = array();
        $collectionArraysKeys = array();
        $collectionArraysCounts = array();

        foreach ($this->toList() as $value) {
            $valueCount = count($value);
            if ($valueCount !== count($value, COUNT_RECURSIVE)) {
                throw new LogicException('Cannot find the cartesian product of a multidimensional array');
            }

            $collectionArraysKeys[] = array_keys($value);
            $collectionArraysCounts[] = $valueCount;
            $collectionArrays[] = $value;
        }

        $result = array();
        $lastIndex = count($collectionArrays) - 1;
        // holds the indexes of the arrays that generate the current combination
        $currentIndexes = array_fill(0, $lastIndex + 1, 0);

        $changeIndex = $lastIndex;

        while (!($changeIndex === 0 && $currentIndexes[0] === $collectionArraysCounts[0])) {
            $currentCombination = array_map(function ($value, $keys, $index) {
                return $value[$keys[$index]];
            }, $collectionArrays, $collectionArraysKeys, $currentIndexes);

            if ($filter === null || $filter($currentCombination)) {
                $result[] = ($operation === null) ? $currentCombination : $operation($currentCombination);
            }

            $currentIndexes[$lastIndex]++;

            for ($changeIndex = $lastIndex; $currentIndexes[$changeIndex] === $collectionArraysCounts[$changeIndex] && $changeIndex > 0; $changeIndex--) {
                $currentIndexes[$changeIndex] = 0;
                $currentIndexes[$changeIndex - 1]++;
            }
        }

        return new Collection($result);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Collection\CollectionInterface
     */
    public function transpose()
    {
        $arrayValue = $this->toList();
        $length = count(current($arrayValue));
        $result = array();
        foreach ($arrayValue as $column => $row) {
            if (count($row) != $length) {
                throw new LogicException('Child arrays do not have even length');
            }
        }

        for ($column = 0; $column < $length; $column++) {
            $result[] = array_column($arrayValue, $column);
        }

        return new Collection($result);
    }

    /**
     * Returns a callable that can be used to extract a property or column from
     * an array or object based on a dot separated path.
     *
     * @param string|callable $callback A dot separated path of column to follow
     * so that the final one can be returned or a callable that will take care
     * of doing that.
     * @return callable
     */
    protected function _propertyExtractor($callback)
    {
        if (!is_string($callback)) {
            return $callback;
        }

        $path = explode('.', $callback);

        if (strpos($callback, '{*}') !== false) {
            return function ($element) use ($path) {
                return $this->_extract($element, $path);
            };
        }

        return function ($element) use ($path) {
            return $this->_simpleExtract($element, $path);
        };
    }

    /**
     * Returns a column from $data that can be extracted
     * by iterating over the column names contained in $path.
     * It will return arrays for elements in represented with `{*}`
     *
     * @param array|\ArrayAccess $data Data.
     * @param array $path Path to extract from.
     * @return mixed
     */
    protected function _extract($data, $path)
    {
        $value = null;
        $collectionTransform = false;

        foreach ($path as $i => $column) {
            if ($column === '{*}') {
                $collectionTransform = true;
                continue;
            }

            if ($collectionTransform &&
                !($data instanceof Traversable || is_array($data))) {
                return null;
            }

            if ($collectionTransform) {
                $rest = implode('.', array_slice($path, $i));

                return (new Collection($data))->extract($rest);
            }

            if (!isset($data[$column])) {
                return null;
            }

            $value = $data[$column];
            $data = $value;
        }

        return $value;
    }

    /**
     * Returns a column from $data that can be extracted
     * by iterating over the column names contained in $path
     *
     * @param array|\ArrayAccess $data Data.
     * @param array $path Path to extract from.
     * @return mixed
     */
    protected function _simpleExtract($data, $path)
    {
        $value = null;
        foreach ($path as $column) {
            if (!isset($data[$column])) {
                return null;
            }
            $value = $data[$column];
            $data = $value;
        }

        return $value;
    }

    /**
     * Returns a callable that receives a value and will return whether or not
     * it matches certain condition.
     *
     * @param array $conditions A key-value list of conditions to match where the
     * key is the property path to get from the current item and the value is the
     * value to be compared the item with.
     * @return callable
     */
    protected function _createMatcherFilter(array $conditions)
    {
        $matchers = array();
        foreach ($conditions as $property => $value) {
            $extractor = $this->_propertyExtractor($property);
            $matchers[] = function ($v) use ($extractor, $value) {
                return $extractor($v) == $value;
            };
        }

        return function ($value) use ($matchers) {
            foreach ($matchers as $match) {
                if (!$match($value)) {
                    return false;
                }
            }

            return true;
        };
    }


}
