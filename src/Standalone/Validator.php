<?php
namespace Webiik;

/**
 * Class Validator
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Validator
{
    private $filters = [];

    private $dataObjects = [];

    private $res = [];

    /**
     * Add user defined filter
     * Closure has two required params ($data, array $paramsArr)
     * @param string $name
     * @param \Closure $closure
     */
    public function addfilter($name, \Closure $closure)
    {
        $this->filters[$name] = $closure;
    }

    /**
     * Add data to validate
     * @param string $name
     * @param mixed $data
     * @return ValidatorData
     */
    public function addData($name, $data)
    {
        $dataObject = new ValidatorData($name, $data);
        $this->dataObjects[$name] = $dataObject;

        return $dataObject;
    }

    /**
     * Validate data
     * If all data are ok, return empty array.
     * If some data don't pass, return:
     * ['err' => [$name => array of data related messages], 'messages' => [array of all messages]]
     * @return array
     */
    public function validate()
    {
        foreach ($this->dataObjects as $dataObjectName => $dataObject) {

            /** @var $dataObject ValidatorData */

            $isRequired = $dataObject->isRequired();
            $data = $dataObject->getData();
            $filters = $dataObject->getFilters();

            // If dataObject isn't required and is empty continue to next dataObject
            if (!$isRequired && !$data) {
                continue;
            }

            // If dataObject has some filters, validate data in dataObject against these filters
            if (!empty($filters)) {

                foreach ($filters as $filterName => $paramsArr) {

                    if (method_exists($this, $filterName)) {
                        $filter = $this->$filterName();
                    } else {
                        $filter = $this->filters[$filterName];
                    }

                    // If filter results to false, add error to response array
                    if (!$filter($data, $paramsArr)) {
                        $msg = isset($paramsArr['msg']) ? $paramsArr['msg'] : false;
                        $this->res['err'][$dataObjectName][] = $msg;
                        if ($msg) {
                            $this->res['messages'][] = $msg;
                        }
                    }

                }

            }
        }

        return $this->res;
    }

    /**
     * Signature:
     * ->filter('required', [
     *   'msg' => 'Fill name.',
     *   'when' => ['lastname', 'street'],
     *   'method' => 'AND'
     * ]);
     */
    private function required()
    {
        return function ($data, array $params) {

            $dataResArr = [];
            $isRequired = true;

            // Required only when other data objects have data
            if (isset($params['when']) && is_array($params['when'])) {

                // Iterate required data objects
                // Check if data object has some data and store info about it
                foreach ($params['when'] as $dataObjectName) {

                    if (isset($this->dataObjects[$dataObjectName])) {

                        /** @var $dataObject ValidatorData */
                        $dataObject = $this->dataObjects[$dataObjectName];
                        $dataResArr[] = $dataObject->getData() ? true : false;
                    } else {
                        $dataResArr[] = false;
                    }
                }

                // If 'method' of 'when' is 'AND', it means that data are required
                // only when all of data objects defined in 'when' has data
                if (isset($params['method']) && $params['method'] == 'AND') {

                    foreach ($dataResArr as $bool) {
                        if (!$bool) {
                            $isRequired = false;
                        }
                    }

                } else {
                    // If 'method' is different, it means that data are required
                    // when some of data objects defined in 'when' has data

                    $isRequired = false;

                    foreach ($dataResArr as $bool) {
                        if ($bool) {
                            $isRequired = true;
                        }
                    }
                }
            }

            if ($isRequired && (!$data || $data == '' || empty($data))) {
                return false;
            }

            return true;
        };
    }

    /**
     * Signature:
     * ->filter('minLength', [
     *   'msg' => 'String is too short.',
     *   'length' => 5, (required)
     * ]);
     */
    private function minLength()
    {
        return function ($data, array $params) {
            if (mb_strlen($data) < $params['length']) {
                return false;
            }
            return true;
        };
    }

    /**
     * Signature:
     * ->filter('maxLength', [
     *   'msg' => 'String is too long.',
     *   'length' => 5, (required)
     * ]);
     */
    private function maxLength()
    {
        return function ($data, array $params) {
            if (mb_strlen($data) > $params['length']) {
                return false;
            }
            return true;
        };
    }

    /**
     * Signature:
     * ->filter('length', [
     *   'msg' => 'String length is out of range.',
     *   'length' => 5 or range [2,5] (required)
     * ]);
     */
    private function length()
    {
        return function ($data, array $params) {

            $length = mb_strlen($data);

            if (is_array($params['length'])) {

                $minLength = $params['length'][0];
                $maxLength = $params['length'][1];

                if ($length < $minLength || $length > $maxLength) {
                    return false;
                }
            }

            if ($length != $params['length']) {
                return false;
            }

            return true;
        };
    }

    /**
     * Signature:
     * ->filter('url', [
     *   'msg' => 'URL address is not valid.',
     * ]);
     */
    private function url()
    {
        return function ($data, array $params) {
            return filter_var($data, FILTER_VALIDATE_URL) === false ? false : true;
        };
    }

    /**
     * Signature:
     * ->filter('email', [
     *   'msg' => 'Invalid email address.',
     * ]);
     */
    private function email()
    {
        return function ($data, array $params) {

            $pattern = '/^[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\@[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\.[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~\.]{2,63}$/';

            preg_match($pattern, $data, $match);

            if (empty($match)) {
                return false;
            }

            return true;
        };
    }

    /**
     * Signature:
     * ->filter('regex', [
     *   'msg' => 'Data doesn't match criteria.',
     *   'pattern' => '/\d/' (required)
     * ]);
     */
    private function regex()
    {
        return function ($data, array $params) {

            preg_match($params['pattern'], $data, $match);

            if (empty($match)) {
                return false;
            }

            return true;
        };
    }

    /**
     * Signature:
     * ->filter('name', [
     *   'msg' => 'Invalid name.',
     *   'maxNamePartLength' => 10,
     *   'maxNameParts' => 5,
     * ]);
     */
    private function name()
    {
        return function ($data, array $params) {

            $name = explode(' ', $data);

            if (!isset($name[1])) {
                return false;
            }

            $i = 0;
            foreach ($name as $npart) {
                if (!preg_match("/^[\p{L}\p{Mn}\p{Pd}'\x{2019}]+$/u", $npart)) {
                    return false;
                }

                if (isset($params['maxNamePartLength'])) {
                    if (mb_strlen($npart, 'utf-8') > $params['maxNamePartLength']) {
                        return false;
                    }
                }

                if (isset($params['maxNameParts'])) {
                    if ($i > $params['maxNameParts']) {
                        return false;
                    }
                }

                $i++;
            }

            return true;
        };
    }

    /**
     * Signature:
     * ->filter('numeric', [
     *   'msg' => 'Value is not numeric or is out of range.',
     *   'min' => 5,
     *   'max' => 5,
     *   'equal' => 5,
     * ]);
     */
    private function numeric()
    {
        return function ($data, array $params) {

            if (is_numeric($data)) {

                if (isset($params['min']) && $data < $params['min']) {
                    return false;
                }

                if (isset($params['max']) && $data > $params['max']) {
                    return false;
                }

                if (isset($params['equal']) && $data != $params['equal']) {
                    return false;
                }

                return true;
            }

            return false;
        };
    }

    /**
     * Signature:
     * ->filter('float', [
     *   'msg' => 'Value is not float or is out of range.',
     *   'min' => 5,
     *   'max' => 5,
     *   'equal' => 5,
     * ]);
     */
    private function float()
    {
        return function ($data, array $params) {

            if (is_float($data)) {

                if (isset($params['min']) && $data < $params['min']) {
                    return false;
                }

                if (isset($params['max']) && $data > $params['max']) {
                    return false;
                }

                if (isset($params['equal']) && $data != $params['equal']) {
                    return false;
                }

                return true;
            }

            return false;
        };
    }

    /**
     * Signature:
     * ->filter('int', [
     *   'msg' => 'Value is not int or is out of range.',
     *   'min' => 5,
     *   'max' => 5,
     *   'equal' => 5,
     * ]);
     */
    private function int()
    {
        return function ($data, array $params) {

            if (is_int($data)) {

                if (isset($params['min']) && $data < $params['min']) {
                    return false;
                }

                if (isset($params['max']) && $data > $params['max']) {
                    return false;
                }

                if (isset($params['equal']) && $data != $params['equal']) {
                    return false;
                }

                return true;
            }

            return false;
        };
    }

}