# File cache
Tiny file cache for your web project.

## Installation
FileCache is part of [Webiik platform](readme.md). Before using FileCache in your project, install it with the following command:
```bash
composer require mihi/webiik
```

## How to use it?
```php
// Instatiate cache
$fc = new FileCache();

// Setup cache
$fc->setDir(__DIR__ . '/cache'); // dir MUST exist
$fc->setExtension('cache');

// Store some data into cache file with unlimited expiration time
$fileCache->set('cats', ['Garfield', 'Pusheen', 'Tom', 'Kitty', 'Scratchy']);

// Get data from cache file
$cats = $fileCache->get('cats');

// Delete cache file
$fileCache->delete('cats');
```

## Description of provided methods

#### setDir(string $dir)
Sets cache directory. This directory must exist.

#### setExtension(string $ext)
Sets cache file extension.

#### set($key, $value, $timestamp = 0):bool
Stores cache file with optional expiration date. On success returns true, otherwise false.

#### get($key)
Gets data from cache file. On success returns true, otherwise false. If cache file is expired, it deletes expired cache file.

#### delete($key)
Deletes cache file. On success returns true, otherwise false.