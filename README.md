[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/collection.svg?style=flat-square)](https://packagist.org/packages/cakephp/collection)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Collection Library For Old PHP

The collection classes provide a set of tools to manipulate arrays or Traversable objects.
If you have ever used underscore.js, you have an idea of what you can expect from the collection classes.

## Warning

**This library is clone from [Official CakePHP Collection](https://github.com/cakephp/collection). The official library is not supported php version less than 5.6,  but this libarary can works with old php version 5.3, 5.4, 5.5. If your php version greater than 5.6.0, please use [Official CakePHP Collection](https://packagist.org/packages/cakephp/collection).**

You can not use some features, because old php is not supported:
  * `Collection\CollectionTrait` is not supported.
  * Functions `listNested()`, `zip()` in class `Collection` is not supported.

## Usage

Collections can be created using an array or Traversable object.  A simple use of a Collection would be:

```php
use Cake\Collection\Collection;

$items = ['a' => 1, 'b' => 2, 'c' => 3];
$collection = new Collection($items);

// Create a new collection containing elements
// with a value greater than one.
$overOne = $collection->filter(function ($value, $key, $iterator) {
    return $value > 1;
});
```

The `Collection\CollectionTrait` allows you to integrate collection-like features into any Traversable object
you have in your application as well.

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/3.0/en/core-libraries/collections.html)
