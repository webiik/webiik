<?php
namespace Webiik;

/**
 * Class Conversion
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Conversion
{
    /**
     * Conversions storage
     * pattern: [
     *      [conversion_category] => [
     *          'unit' => conversion_rate_between_units
     *      ]
     * ]
     * For example: [
     *      ['length'] => [
     *          'cm' => 100
     *          'm' => 1
     *          'km' => 0.001
     *      ]
     * ]
     * Note: Conversion is possible only between units from the same conversion_category
     * @var array
     */
    private $conversion = [];

    /**
     * Add single conversion
     * @param $unit
     * @param $rate
     * @param $category
     */
    public function addConv($unit, $rate, $category)
    {
        $this->conversion[$category][$unit] = $rate;
    }

    /**
     * Add one or multiple conversions in array
     * @param $arr
     */
    public function addConvArr($arr)
    {
        $this->conversion = array_merge($this->conversion, $arr);
    }

    /**
     * Return conversion or false|string if conversion fails
     * @param $from
     * @param $to
     * @param bool $decimals
     * @return bool|float|string
     */
    public function conv($from, $to, $decimals = false)
    {
        preg_match_all('/([\d\.\,\s]+)([^\d]+)/', $from, $matches);

        $i = 0;
        $conv = false;
        foreach ($matches[1] as $fromVal) {

            $fromVal = floatval(str_replace(' ', '', $fromVal));
            if (!is_numeric($fromVal)) {
                return 'Parameter ' . htmlspecialchars($fromVal) . ' must be a number.';
            }

            $fromUnit = $matches[2][$i];

            $rates = $this->getRates($fromUnit, $to);

            if ($rates) {
                $conv += $rates[1] / $rates[0] * $fromVal;
            } else {
                return 'Units can\'t be converted between each other.';
            }

            $i++;
        }

        return is_numeric($decimals) ? number_format($conv, $decimals) : $conv;
    }

    /**
     * Return conversion rates needed for conversion
     * or false if conversion is not possibe.
     * @param $fromUnit
     * @param $toUnit
     * @return array|bool
     */
    private function getRates($fromUnit, $toUnit)
    {
        foreach ($this->conversion as $category => $arr) {

            foreach ($arr as $unit => $rate) {

                $units = explode('__', $unit);

                if (in_array(trim($fromUnit), $units)) $fromRate = $rate;
                if (in_array(trim($toUnit), $units)) $toRate = $rate;
            }

            if (isset($fromRate) || isset($toRate)) break;
        }

        if (!isset($fromRate) || !isset($toRate)) return false;

        return [$fromRate, $toRate];
    }
}