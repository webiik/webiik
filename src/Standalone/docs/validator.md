> Don't use this documentation, it's dev version! Current documentation will be added later this year.

# Validator
Validates data against specified filter. Very useful for API call data or form data validation.  

## Installation
Validator is part of [Webiik](readme.md), but can be used separately. Install it with the following command:
```bash
composer require mihi/webiik
```

## How to use it?
```php
// Create instance
$validaor = new Validator();

// Add data to validate
$validator->addData('password', 'supersecretpassword')
          ->filter('required', ['msg' => 'Fill password.'])
          ->filter('minLength', ['msg' => 'Password is too short.', 'length' => 6]);

// Validate data
$res = $validator->validate();
```

## Predefined filters

- #### required

    Data with this filter are required.

    ```php
    $data->filter('required', ['msg' => 'Error message.', 'when' => ['differentAddress'], 'method' => 'AND']);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
    - __when__
    Optional. Array. Array of data names. Required filter will apply only when one of data names has data too.
    
    - __method__
    Optional. String. Related to 'when', can be set to 'AND'. Required filter will apply only when all data names has data too.
     
- #### minLength

    Data with this filter must be longer than minimal length.

    ```php
    $data->filter('minLength', ['msg' => 'String is too short.', 'length' => 5]);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
    - __length__
    Required. Integer. Minimal data length.

- #### maxLength

    Data with this filter must be shorter than maximal length.

    ```php
    $data->filter('maxLength', ['msg' => 'String is too long.', 'length' => 5]);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
    - __length__
    Required. Integer. Maximal data length.
    
- #### length

    Data with this filter must match specified length.

    ```php
    $data->filter('length', ['msg' => 'String length is out of range.', 'length' => 5]);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
    - __length__
    Required. Integer or Array. Exact length or length range.
    
- #### url

    Data with this filter must be valid URL.

    ```php
    $data->filter('url', ['msg' => 'URL address is not valid.']);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
- #### email

    Data with this filter must be valid email address.

    ```php
    $data->filter('email', ['msg' => 'Invalid email address.']);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
- #### regex

    Data with this filter must match given regex pattern.

    ```php
    $data->filter('regex', ['msg' => 'Invalid email address.', 'pattern' => '/\d/']);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
    - __pattern__
    Required. String. Regex pattern.
    
- #### numeric, float, int

    Data with this filter must match criteria of PHP's is_numeric, is_float, is_int function. Range can be specified.

    ```php
    $data->filter('numeric', ['msg' => 'Value is not numeric or is out of range.', 'min' => 5]);
    $data->filter('float', ['msg' => 'Value is not float or is out of range.', 'max' => 5]);
    $data->filter('int', ['msg' => 'Value is not int or doesn't match.', 'equal' => 5]);
    ```

    __Parameters__

    - __msg__
    Optional. String. Error message.
    
    - __min__
    Optional. Integer. Minimal value.
    
    - __max__
    Optional. Integer. Maximal value.
        
    - __equal__
    Optional. Integer. Exact value.
    
## Description of provided methods

- `addfilter(string $name, \Closure $closure)`
Adds custom filter. Closure has two params ($data, array $paramsArr). Every filter must return boolean.

- `addData(string $name, mixed $data):ValidatorData`
Adds data to validate. Return ValidatorData object.

- `validate:array`
Validates data. If all data are ok, returns empty array. If some data don't pass, returns \['err' => \[$dataName => array of data related messages\], 'messages' => array of all messages\]
