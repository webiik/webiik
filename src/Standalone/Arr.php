<?php
namespace Webiik;

/**
 * Class Arr
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Arr
{
    /**
     * Set an item into array using dot notation
     * @param array $array
     * @param string $key
     * @param mixed $val
     */
    public function set(&$array, $key, $val)
    {
        $keys = explode('.', $key);
        $context = &$array;
        foreach ($keys as $ikey) {
            $context = &$context[$ikey];
        }

        $context = $val;
    }

    /**
     * Add an item into array using dot notation
     * @param array $array
     * @param string $key
     * @param mixed $val
     */
    public function add(&$array, $key, $val)
    {
        $keys = explode('.', $key);
        $context = &$array;
        foreach ($keys as $ikey) {
            $context = &$context[$ikey];
        }

        if (empty($context)) {
            $context = $val;
            return;
        }

        if (!is_array($val)) {
            $val = [$val];
        }

        if (is_array($context)) {
            $context = array_merge($context, $val);
        } else {
            $context = array_merge([$context], $val);
        }
    }

    /**
     * Get an item from array using dot notation
     * On success return key value, otherwise false
     * @param array $array
     * @param string $key
     * @return mixed|bool
     */
    public function get($array, $key)
    {
        $val = false;
        $context = $array;
        $keys = explode('.', $key);

        foreach ($keys as $ikey) {
            if (isset($context[$ikey])) {
                $context = $context[$ikey];
                $val = $context;
            } else {
                $val = false;
            }
        }

        return $val;
    }

    /**
     * Delete an item from array using dot notation
     * On success return key value, otherwise false
     * @param array $array
     * @param string $key
     */
    public function delete(&$array, $key)
    {
        $keys = explode('.', $key);
        $context = &$array;
        $last = array_pop($keys);

        foreach ($keys as $ikey) {
            $context = &$context[$ikey];
        }

        unset($context[$last]);
    }

    /**
     * Return keys that are different in $array1 from $array2
     * @param $array1
     * @param $array2
     * @return array
     */
    public function diffMultiABKeys($array1, $array2)
    {
        $result = [];
        foreach ($array1 as $key => $val) {
            if (array_key_exists($key, $array2)) {
                if (is_array($val) && is_array($array2[$key]) && !empty($val)) {
                    $temRes = $this->diffMultiABKeys($val, $array2[$key]);
                    if (count($temRes) > 0) {
                        $result[$key] = $temRes;
                    }
                }
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }

    /**
     * Return values that are different in $array1 from $array2
     * @param $array1
     * @param $array2
     * @return array
     */
    public function diffMultiAB($array1, $array2)
    {
        $result = [];

        foreach ($array1 as $key => $val) {

            if (array_key_exists($key, $array2)) {

                if (is_array($val) && is_array($array2[$key])) {

                    $temRes = $this->diffMultiAB($val, $array2[$key]);

                    if (count($temRes) > 0) {
                        $result[$key] = $temRes;
                    }

                } else {

                    if (is_array($val) && !is_array($array2[$key])) {
                        $result[$key] = $val;
                    } else if (!is_array($val) && is_array($array2[$key])) {
                        $result[$key] = $val;
                    } else if ($array2[$key] != $val) {
                        $result[$key] = $val;
                    }

                }

            } else {

                $result[$key] = $val;
            }
        }

        return $result;
    }

    /**
     * Return keys that are different across $array1 and $array2
     * @param $array1
     * @param $array2
     * @return array
     */
    public function diffMultiKeys($array1, $array2)
    {
        $diffAB = $this->diffMultiABKeys($array1, $array2);
        $diffBA = $this->diffMultiABKeys($array2, $array1);
        return array_merge_recursive($diffAB, $diffBA);
    }

    /**
     * Return values that are different across $array1 and $array2
     * @param $array1
     * @param $array2
     * @return array
     */
    public function diffMulti($array1, $array2)
    {
        $diffAB = $this->diffMultiAB($array1, $array2);
        $diffBA = $this->diffMultiAB($array2, $array1);
        return array_merge_recursive($diffAB, $diffBA);
    }

    /**
     * Todo: Get result array affected by callback
     * Iterate multidimensional array and call callback on every key iteration
     * @param $array
     * @param \Closure $callback
     * @param int $deep
     * @param string $path
     * @return mixed
     */
    public function forEachMulti($array, \Closure $callback, $deep = 0, $path = '')
    {
        $deep++;

        foreach ($array as $key => $value) {

            if ($path == '') {
                $newPath = $key;
            } else {
                $newPath = $path . '.' . $key;
            }

            if (is_array($value)) {
                $callback($array, $key, $value, $newPath, $deep);
                $array = $this->forEachMulti($value, $callback, $deep, $newPath);
            } else {
                $callback($array, $key, $value, $newPath, $deep);
            }
        }

        return $array;
    }
}