<?php
namespace Webiik;

/**
 * Class Format
 * @package Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Format
{
    /**
     * On success return array with formatted fullname, firstname and lastname
     * On error return array with name filled with original data
     * @param $name
     * @return array
     */
    public function nameArr($name)
    {
        $validName['fullname'] = $name;
        $validName['firstname'] = '';
        $validName['lastname'] = '';

        $name = explode(' ', trim($name));

        $i = 0;
        foreach ($name as $namePart) {

            if ($i == 0) {
                $validName['firstname'] = $this->capitalize($namePart);
            } elseif ($i == 1) {
                $validName['lastname'] = $this->capitalize($namePart);
            } else {

                if (mb_strlen($namePart) < 3) {
                    $validName['lastname'] .= ' ' . mb_strtolower($namePart);
                } else {
                    $validName['lastname'] .= ' ' . $this->capitalize($namePart);
                }
            }

            $i++;
        }

        if ($i > 0) {
            $validName['fullname'] = $validName['firstname'] . ' ' . $validName['lastname'];
        }

        return $validName;
    }

    /**
     * On success return formatted name
     * On error return original data
     * @param $name
     * @return mixed
     */
    public function name($name)
    {
        $nameArr = $this->nameArr($name);
        return $nameArr['fullname'];
    }

    /**
     * On success return formatted $url
     * On error return original data
     * @param $url
     * @return bool|string
     */
    public function url($url)
    {
        $pu = parse_url($url);

        if ($pu) {

            $url = false;

            if (isset($pu['scheme'])) {
                $url .= strtolower($pu['scheme']) . '://';
            }

            if (isset($pu['host'])) {
                $url .= strtolower($pu['host']);
            }

            if (isset($pu['path'])) {
                $url .= strtolower($pu['path']);
            }

            if (isset($pu['query'])) {
                $url .= '?' . $pu['query'];
            }

        }

        return $url;
    }

    /**
     * Capitalize string (multi byte)
     * @param $str
     * @return string
     */
    public function capitalize($str)
    {
        $str = mb_strtolower($str, 'utf-8');
        $fc = mb_strtoupper(mb_substr($str, 0, 1, 'utf-8'), 'utf-8');
        return $fc . mb_substr($str, 1, null, 'utf-8');
    }
}