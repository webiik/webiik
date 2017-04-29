<?php
namespace Webiik;

/**
 * Class ValidatorData
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class ValidatorData
{
    private $name;

    private $data;

    private $filters = [];

    public function __construct($name, $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Add filter
     * @param $name
     * @param array $params
     * @return ValidatorData
     */
    public function filter($name, $params = [])
    {
        $this->filters[$name] = $params;
        return $this;
    }

    /**
     * Get filters
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get data
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return true if current ValidatorData object is required, otherwise false
     * @return bool
     */
    public function isRequired()
    {
        if (isset($this->filters['required'])) {
            return true;
        }

        return false;
    }

}