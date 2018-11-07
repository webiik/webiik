<?php

namespace Webiik;

/**
 * Class Translation
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Translation
{
    /**
     * @var Conversion
     */
    private $conv;

    /**
     * Array of user defined translation fallbacks
     * Look at setFallback() for more info
     * @var array
     */
    private $fallbacks = [];

    /**
     * Array of user defined translations
     * Look at addTrans() for more info
     * @var array
     */
    private $translation = [];

    /**
     * Array of user defined formats
     * Look at add{*}Format() methods for more info
     * @var array
     */
    private $types = [];

    /**
     * Indicates if Translation will log missing values
     * @var bool
     */
    private $logWarnings = false;

    /**
     * Custom logger
     * @var callable
     */
    private $logger;

    /**
     * Language of requested translation
     * See more in setLang()
     * @var
     */
    protected $lang;

    /**
     * Activate or deactivate logging of missing values
     * @param $bool
     */
    public function activateLogWarnings($bool)
    {
        $this->logWarnings = $bool;
    }

    /**
     * Add logger to enable error logging
     * @param callable $logger
     */
    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set language of requested translation
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * Return current lang of translation
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Add date format for given lang
     * @param string $lang : eg. 'en'
     * @param string $formatName : eg. 'medium'
     * @param string $formatPattern : php date() format eg. 'j. M Y'
     * @throws \Exception
     * Note: Use $formatName 'default' to format date types without format signature
     */
    public function setDateFormat($lang, $formatName, $formatPattern)
    {
        $this->isString($formatName);
        $this->isString($formatPattern);
        $this->isString($lang);
        $this->types['date'][$lang][$formatName] = $formatPattern;
    }

    /**
     * Add time format for given lang
     * @param string $formatName : eg. 'medium'
     * @param string $formatPattern : php date() format eg. 'H:i:s'
     * @param string $lang : eg. 'en'
     * @throws \Exception
     * Note: Use $formatName 'default' to format time types without format signature
     */
    public function setTimeFormat($lang, $formatName, $formatPattern)
    {
        $this->isString($formatName);
        $this->isString($formatPattern);
        $this->isString($lang);
        $this->types['time'][$lang][$formatName] = $formatPattern;
    }

    /**
     * Add number format for given lang
     * @param string $lang : eg. 'en'
     * @param string $formatName : eg. 'default'
     * @param array $formatPattern : php number_format() format eg. [2, ',', ' ']
     * @throws \Exception
     * Note: Use $formatName 'default' to format number types without format signature
     */
    public function setNumberFormat($lang, $formatName, array $formatPattern)
    {
        $this->isString($formatName);
        $this->isArrSeq($formatPattern);
        $this->isString($lang);
        $this->types['number'][$lang][$formatName] = $formatPattern;
    }

    /**
     * Add number format for given lang
     * @param string $lang : eg. 'en'
     * @param string $currencyName : eg. 'usd'
     * @param string $formatPattern : must contain %i eg. '$ %i'
     * @throws \Exception
     */
    public function setCurrencyFormat($lang, $currencyName, $formatPattern)
    {
        $this->isString($currencyName);
        $this->isString($formatPattern);
        if (strpos($formatPattern, '%i') === false) {
            throw new \Exception('Currency pattern must include the following characters: %i');
        }
        $this->types['currency'][$lang][$currencyName] = $formatPattern;
    }

    /**
     * Set long month names translation
     * @param array $keyValArr : eg. ['January' => 'Leden']
     * @param string $lang : eg. 'en'
     * @throws \Exception
     */
    public function setLongMonthNamesTrans(array $keyValArr, $lang)
    {
        $this->isArrAssoc($keyValArr);
        $this->isString($lang);
        $this->types['date'][$lang]['monthsLong'] = $keyValArr;
    }

    /**
     * Set short month names translation
     * @param array $keyValArr : eg. ['Jan' => 'Led']
     * @param string $lang : eg. 'en'
     * @throws \Exception
     */
    public function setShortMonthNamesTrans(array $keyValArr, $lang)
    {
        $this->isArrAssoc($keyValArr);
        $this->isString($lang);
        $this->types['date'][$lang]['monthsShort'] = $keyValArr;
    }

    /**
     * Set long month names translation
     * @param array $keyValArr : eg. ['Mon' => 'Pon']
     * @param string $lang : eg. 'en'
     * @throws \Exception
     */
    public function setShortDayNamesTrans(array $keyValArr, $lang)
    {
        $this->isArrAssoc($keyValArr);
        $this->isString($lang);
        $this->types['date'][$lang]['daysShort'] = $keyValArr;
    }

    /**
     * Set long month names translation
     * @param array $keyValArr : eg. ['Monday' => 'Pondělí']
     * @param string $lang : eg. 'en'
     * @throws \Exception
     */
    public function setLongDayNamesTrans(array $keyValArr, $lang)
    {
        $this->isArrAssoc($keyValArr);
        $this->isString($lang);
        $this->types['date'][$lang]['daysLong'] = $keyValArr;
    }

    /**
     * Set fallback languages for given $lang
     * Priority of fallback is determined by order in array
     * @param string $lang : eg. 'en'
     * @param array $fallbacks : eg. ['es', 'de']
     */
    public function setFallback($lang, array $fallbacks)
    {
        $this->isString($lang);
        $this->isArrSeq($fallbacks);
        $this->fallbacks[$lang] = $fallbacks;
    }

    /**
     * Add translation record to translation array for given lang.
     * Use key parameter with dot notation to add translation into specific key in multidimensional array.
     * If you don't use key parameter then $val must be associative array
     *
     * Translation can be written in syntax very similar to ICU messages eg.:
     * 1)
     * Simple variable signature:
     * {variableName}
     * Example:
     * Hello {name}!
     *
     * 2)
     * 'time' and 'date' types signature:
     * {variableName, type, format(optional)}
     *
     * Example:
     * Today is {timeStamp, date}, the time is {timeStamp, time}.
     *
     * Example with formatting:
     * Today is {timeStamp, date, long}, the time is {timeStamp, time, medium}.
     *
     * 3)
     * 'plural' type signature:
     * {variableName, type, =num {message}...}
     *
     * Example:
     * {numCats, plural, =0 {Any cat have} =1 {One cat has} =2+ {{numCats} cats have}} birthday.
     *
     * Available operators:
     * =0 : exact count
     * =1-4 : range
     * =5+ : greater than
     *
     * 4)
     * 'select' type signature:
     * {variableName, type, =string {message}...}
     *
     * Example:
     * {gender, select, =male {He} =female {She}} likes vanilla ice cream.
     *
     * Available operators:
     * =string : exact string
     *
     * 5)
     * 'currency' type signature:
     * {variableName, type, currencyFormatVariableName}
     *
     * Example:
     * The car costs {price, currency, currency}.
     *
     * 6)
     * 'conv' type signature:
     * {variableName, type, from, to, unitsFormatting(optional)}
     *
     * Example:
     * Maximal allowed speed is {speed, conv, kmh, mph, su}
     *
     * Available units formatting:
     * s - add space between number and unit
     * u - units in uppercase
     * l - units in lowercase
     * f - first case upper
     *
     * Note: To work with conv type inject configured Conversion obj with method addConv()
     *
     * @param string $lang : eg. 'en'
     * @param string|array $val : eg. ['txt1' => 'Hello {name}! I feel {mood}.', 'txt2' => '{greeting} World!']
     * @param string|bool $key : eg. 'dot.notation.key'
     * @param $val
     * @throws \Exception
     */
    public function addTrans($lang, $val, $key = false)
    {
        $this->isString($lang);
        if (!isset($this->translation[$lang])) $this->translation[$lang] = [];

        if ($key) {
            $this->isString($key);

            // Prepare context according to dot notation
            $pieces = explode('.', $key);
            $context = &$this->translation[$lang];
            foreach ($pieces as $piece) {
                $context = &$context[$piece];
            }

            // Add value to prepared context
            if (is_array($context)) {
                $context = array_merge($context, $val);
            } else {
                $context = $val;
            }

        } else {
            $this->isArrAssoc($val);
            $this->translation[$lang] = $this->array_merge_recursive_distinct($this->translation[$lang], $val);
        }
    }

    /**
     * Return parsed string from translation array by given key
     * @param string $key : 'txt1'
     * @param array $val : eg. ['name' => 'World', 'mood' => 'good']
     * @return bool|string
     * @throws \Exception
     */
    public function _p($key, array $val)
    {
        $this->isLangSet();
        $this->isString($key);
        $this->isArrAssoc($val);
        $string = $this->getRow($key);
        return $string ? $this->parse($string, $val) : false;
    }

    /**
     * Return merged and parsed translation of all langs.
     * Warning! Same keys will be overwritten and route translations will be removed.
     * @param array $val
     */
    // Todo: _pAll method
    public function _pAll(array $val)
    {

    }

    /**
     * Return string from translation array by given key
     * @param $key : 'txt1'
     * @return bool|mixed
     * @throws \Exception
     */
    public function _t($key)
    {
        $this->isLangSet();
        $this->isString($key);
        $string = $this->getRow($key);
        return $string ? $string : false;
    }

    /**
     * Return key value array of all translations
     * Options:
     * If lang is defined by string, return translations for given lang
     * If lang is false, return merged translation of all langs (Warning! Same keys will be overwritten and route translations will be removed)
     * If lang is true, return all translations sorted by lang
     * @param bool $lang
     * @return array
     * @throws \Exception
     */
    public function _tAll($lang = false)
    {
        $this->isLangSet();

        if (is_string($lang)) {
            if (isset($this->translation[$lang])) {
                return $this->translation[$lang];
            } else {
                return [];
            }
        }

        if (is_bool($lang) && !$lang) {

            $mergedTranslation = [];

            foreach ($this->translation as $lang => $val) {
                $mergedTranslation = $mergedTranslation + $this->translation[$lang];
            }

            unset($mergedTranslation['routes']);

            return $mergedTranslation;
        }

        return $this->translation;
    }

    /**
     * Add Conversion obj and allow to use conv type
     * @param Conversion $conversion
     */
    public function addConv(Conversion $conversion)
    {
        $this->conv = $conversion;
    }

    /**
     * Return key value from $this->translation or false if key does not exist.
     * Multidimensional arrays can be accessed with key dot notation.
     * If logging is set, log error and fallback translations.
     * @param $key
     * @return mixed
     */
    private function getRow($key)
    {
        $val = false;
        $pieces = explode('.', $key);

        // At first try to find key in current lang
        if (isset($this->translation[$this->lang])) {

            $context = $this->translation[$this->lang];

            foreach ($pieces as $piece) {
                if (isset($context[$piece])) {
                    $context = $context[$piece];
                    $val = $context;
                } else {
                    $val = false;
                }
            }
        }

        // If we didn't find key in current lang, we will try to find key in fallback langs
        if (!$val) {

            if (isset($this->fallbacks[$this->lang]) && is_array($this->fallbacks[$this->lang])) {

                foreach ($this->fallbacks[$this->lang] as $fallbackLang) {

                    if (isset($this->translation[$fallbackLang])) {

                        $context = $this->translation[$fallbackLang];
                        foreach ($pieces as $piece) {
                            if (isset($context[$piece])) {
                                $context = $context[$piece];
                                $val = $context;
                            } else {
                                $val = false;
                            }
                        }

                        if ($val) {
                            // Log fallback usage
                            $msg = [
                                'Class' => 'Translation',
                                'Method' => 'getRow',
                                'Key' => $key,
                                'Language' => $this->lang,
                                'Fallback language' => $fallbackLang,
                                'Message' => 'Key \'' . $key . '\' is missing in lang \'' . $this->lang . '\' using \'' . $fallbackLang . '\' lang instead.',
                            ];
                            $this->log($msg);
                            break;
                        }
                    }
                }
            }
        }

        // Log error
        if (!$val) {

            $msg = [
                'Class' => 'Translation',
                'Method' => 'getRow',
                'Key' => $key,
                'Language' => $this->lang,
                'Message' => 'Key \'' . $key . '\' is missing in lang \'' . $this->lang . '\'.',
            ];

            // Log error
            $this->log($msg);
        }

        return $val;
    }

    /**
     * Return value of given array key from given array
     * @param array $arr
     * @param string $paramName (array key)
     * @return string
     */
    private function getParamVal(array $arr, $paramName)
    {
        if (isset($arr[$paramName])) {
            $paramVal = $arr[$paramName];
        } else {
            $paramVal = '{' . $paramName . '}';
        }

        return $paramVal;
    }

    /**
     * Return parsed string
     * Some parsers throws exceptions
     * @param string $string
     * @param array $val
     * @return string
     * @throws \Exception
     */
    private function parse($string, array $val)
    {
        $brackets = $this->extractBrackets($string);

        $bracketNum = 0;
        foreach ($brackets as $bracket) {

            // Get bracket's paramName, type and format
            preg_match_all('/^([^,]+),?\s?(\w+)?,?\s?(.*)$/', $bracket, $matches);
            $paramName = $matches[1][0];
            $type = $matches[2][0];
            $format = $matches[3][0];

            // Get value of paramName
            $value = $this->getParamVal($val, $paramName);

            if (!$type) {
                $bracketResult = $this->getParamVal($val, $paramName);
            }

            if ($type == 'conv' && is_object($this->conv) && $this->conv instanceof Conversion) {
                $bracketResult = $this->parseConv($value, $format);
            }

            if ($type == 'number') {
                $bracketResult = $this->parseNumber($value, $format);
            }

            if ($type == 'currency') {
                $currency = $this->getParamVal($val, $format);
                $bracketResult = $this->parseCurrency($value, $currency);
            }

            if ($type == 'date' || $type == 'time') {
                $bracketResult = $this->parseDateTime($value, $type, $format);
            }

            if ($type == 'plural' || $type == 'select') {
                $bracketResult = $this->parsePluralSelect($value, $format, $val);
            }

            if (isset($bracketResult)) {
                $string = str_replace('{' . $bracket . '}', $bracketResult, $string);
            }

            $bracketNum++;
        }

        return $string;
    }

    /**
     * Return converted value, optionally with value unit
     * @param string $value
     * @param string $format : eg.'kmh, mph, su'
     * @return string
     * @throws \Exception
     */
    private function parseConv($value, $format)
    {
        $fromTo = explode(',', $format);
        $units = '';

        // Add units to $value and format units
        if (isset($fromTo[2])) {

            $units = trim($fromTo[1]);

            if (strpos($fromTo[2], 'u') !== false) {
                $units = strtoupper($units);
            }

            if (strpos($fromTo[2], 'l') !== false) {
                $units = strtolower($units);
            }

            if (strpos($fromTo[2], 'f') !== false) {
                $units = ucfirst($units);
            }

            if (strpos($fromTo[2], 's') !== false) {
                $units = ' ' . $units;
            }
        }

        $result = $this->conv->conv($value . $fromTo[0], $fromTo[1], 0) . $units;

        return $result;
    }

    /**
     * Return formatted number according to $format
     * Return english formatted number, if $format does not correspond to any of user defined formats
     * Remove decimals if they are just .00 etc.
     * Look at $format description to know how to define user formats
     * @param $num
     * @param bool $format
     * @return string
     */
    private function parseNumber($num, $format = false)
    {
        // Get format
        if ((!$format || $format == 'percent' || $format == 'percentage')
            && isset($this->types['number'][$this->lang]['default'])
        ) {
            $numFormat = $this->types['number'][$this->lang]['default'];
        }
        if ($format && isset($this->types['number'][$this->lang][$format])) {
            $numFormat = $this->types['number'][$this->lang][$format];
        }
        if (!isset($numFormat)) $numFormat = [];

        // Show decimals only if needed
        if (!is_float($num)) {
            $numFormat[0] = 0;
        }

        $result = number_format($num, ...$numFormat);

        // Add percent char if format is percent or percentage
        if ($format == 'percent') {
            $result .= ' %';
        }
        if ($format == 'percentage') {
            $result .= '%';
        }

        return $result;
    }

    /**
     * Return formatted currency according to $currency format
     * Throw exception if $currency format does not exist
     * @param $amount
     * @param $currency
     * @return string
     * @throws \Exception
     */
    private function parseCurrency($amount, $currency)
    {
        // Format number value
        $amount = $this->parseNumber($amount);

        // Check if user defined pattern for this currency format
        if (!isset($this->types['currency'][$this->lang][$currency])) {
            throw new \Exception('Missing currency format: \'' . htmlspecialchars($currency) . '\' in arr $types[\'' . $this->lang . '.\'][\'currency\'].');
        }

        $result = str_replace('%i', $amount, $this->types['currency'][$this->lang][$currency]);

        return $result;
    }

    /**
     * Return formatted date or time according to $format
     * Return Y/m/d  formatted date, if $format does not correspond to any of user defined formats
     * Translate long and short month names if user defined translations
     * @param $timestamp
     * @param $type
     * @param $format
     * @return bool|string
     */
    private function parseDateTime($timestamp, $type, $format = false)
    {
        // Get format
        $dateFormat = $type == 'time' ? 'H:i:s' : 'Y/m/d';
        if ($format && isset($this->types[$type][$this->lang][$format])) {
            $dateFormat = $this->types[$type][$this->lang][$format];
        }
        if (!$format && isset($this->types[$type][$this->lang]['default'])) {
            $dateFormat = $this->types[$type][$this->lang]['default'];
        }

        $bracketResult = date($dateFormat, $timestamp);

        // Translate month and day names if we have their translations
        foreach (['monthsLong', 'monthsShort', 'daysLong', 'daysShort'] as $key) {
            if (isset($this->types[$type][$this->lang][$key])) {
                $bracketResult = strtr($bracketResult, $this->types[$type][$this->lang][$key]);
            }
        }

        return $bracketResult;
    }

    /**
     * Return part of $conditionalMessage matching the $value
     * Replace params with their values in the matching conditional message
     * Throw exception if there is no match
     * @param $value
     * @param $conditionalMessage
     * @param $messageParams
     * @throws \Exception
     * @return mixed
     */
    private function parsePluralSelect($value, $conditionalMessage, $messageParams = [])
    {
        // Get the conditions
        preg_match_all('/\=([\d\w]+\-?\+?([\d]+)?)/', $conditionalMessage, $conditions);

        // Get index(key) of the condition which matches the $value
        $conditionKey = 0;
        $conditionKeyFound = false;
        foreach ($conditions[1] as $condition) {
            if (preg_match('/^\w+$/', $condition) && $value == $condition) {
                $conditionKeyFound = true;
                break;
            }
            if (preg_match('/^\d+$/', $condition) && $value == $condition) {
                $conditionKeyFound = true;
                break;
            }
            if (preg_match('/^(\d+)\-(\d+)$/', $condition, $matches)) {
                if ($value >= $matches[1] && $value <= $matches[2]) {
                    $conditionKeyFound = true;
                    break;
                }
            }
            if (preg_match('/^(\d+)\+$/', $condition, $matches)) {
                if ($value >= $matches[1]) {
                    $conditionKeyFound = true;
                    break;
                }
            }
            $conditionKey++;
        }

        if (!$conditionKeyFound) {
            // Log error
            $msg = [
                'Class' => 'Translation',
                'Method' => 'parsePluralSelect',
                'Key' => $value,
                'Language' => $this->lang,
                'Message' => 'Condition key for \'' . $value . '\' not found.',
            ];
            $this->log($msg);
            return $conditionalMessage;
        }

        // Get all brackets in the conditional message
        $formatBrackets = $this->extractBrackets($conditionalMessage);

        // Get conditional message relevant to the $value
        $bracketResult = $formatBrackets[$conditionKey];

        // Replace variable in relevant condition with relevant value
        foreach ($messageParams as $paramName => $value) {
            if (is_numeric($value)) $value = $this->parseNumber($value);
            $bracketResult = str_replace('{' . $paramName . '}', $value, $bracketResult);
        }

        return $bracketResult;
    }

    /**
     * Return array with content of all outer brackets in given string
     * @param $string
     * @return array
     */
    private function extractBrackets($string)
    {
        $extractions = [];
        $openingBrackets = 0;
        $closingBrackets = 0;
        $brackets = [];

        for ($i = 0; $i < strlen($string); $i++) {

            if ($string[$i] == '{') {
                $brackets[] = $i;
                $openingBrackets++;
            }
            if ($string[$i] == '}') {
                $brackets[] = $i;
                $closingBrackets++;
            }

            if ($openingBrackets == $closingBrackets && $closingBrackets > 0) {
                $lastKey = count($brackets);
                $extractions[] = substr($string, $brackets[0] + 1, $brackets[$lastKey - 1] - $brackets[0] - 1);
                $openingBrackets = 0;
                $closingBrackets = 0;
                $brackets = [];
            }
        }

        return $extractions;
    }

    /**
     * Some helper methods
     * Don't copy paste these methods, they return
     * expected results only in environment of this class.
     */
    private function isString($val)
    {
        if (!is_string($val)) {
            throw new \Exception('Parameter must be a string.');
        }
        return true;
    }

    private function isArrAssoc($val)
    {
        if (!is_array($val) || isset($val[0])) {
            throw new \Exception('Parameter must be associative array.');
        }
        return true;
    }

    private function isArrSeq($val)
    {
        if (!is_array($val) || !isset($val[0])) {
            throw new \Exception('Parameter must be sequential array.');
        }
        return true;
    }

    private function isLangSet()
    {
        if (!is_string($this->lang)) {
            throw new \Exception('Set first language of translation with method setLang().');
        }
        return true;
    }

    /**
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * Log error
     * @param $data
     */
    private function log($data)
    {
        if ($this->logWarnings) {

            $logger = $this->logger;

            if (is_callable($logger)) {
                $logger($data);
            } else {
                error_log(json_encode($data) . '\r\n');
            }
        }
    }
}