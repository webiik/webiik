<?php
namespace Webiik;

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
     * Multidimensional array_diff
     * @param $array1
     * @param $array2
     * @return array
     */
    public function diffMulti($array1, $array2)
    {
        $result = array();
        foreach ($array1 as $key => $val) {
            if (array_key_exists($key, $array2)) {
                if (is_array($val) && is_array($array2[$key]) && !empty($val)) {
                    $temRes = $this->diffMulti($val, $array2[$key]);
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
}