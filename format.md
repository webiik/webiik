# Format
Functions for formatting various inputs like URLs, names etc. and getting nicely formatted data.  

## Installation
Format is part of [Webiik](readme.md), but can be used separately. Install it with the following command:
```bash
composer require mihi/webiik
```

## How to use it?
```php
// Create instance
$format = new Format();

// Format some data to be nice
$url = $format->url('HTTP://wwW.ugLY.url/aPI/call?iD=drewERWDF&cc=cC2020');

echo $url; // http://www.ugly.url/api/call?iD=drewERWDF&cc=cC2020
```
    
## Description of provided methods

> Every method returns formatted data on success and original data on error.

- `nameArr(string $name):array`
Returns array with formatted fullname, firstname, lastname. On error returns array\[fullname\] filled with original data.

- `name(string $name):mixed`
Returns formatted name. On error returns original data.

- `url(string $url):string|bool`
Returns formatted URL. On error returns original data.

- `capitalize(string $str):string`
Returns capitalized text. 