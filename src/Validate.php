<?php
namespace Webiik;

/**
 * Class Validate
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Validate
{
    /**
     * Return validated and formatted email on success, otherwise false
     * @param $email
     * @return bool|string
     */
    public function email($email)
    {
        $email = mb_strtolower(strtolower(str_replace('..', '.', trim($email, " .\t\n\r\0\x0B"))));

        if (mb_strlen($email) > 60) {
            return false;
        }

        if (preg_match('/^[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*@[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\.[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~\.]{2,63}$/', $email)) {
            return false;
        }

        return $email;
    }

    /**
     * Return array with validated and formatted firstname and lastname on success, otherwise false
     * @param $name
     * @return bool|string
     */
    public function name($name, $maxNamePartLength = 10, $maxNameParts = 5)
    {
        $name = explode(' ', trim($name, ' '));
        $validName = [];

        if (!isset($name[1])) {
            return false;
        }

        $i = 0;
        foreach ($name as $npart) {
            if (!preg_match("/^[\p{L}\p{Mn}\p{Pd}'\x{2019}]+$/u", $npart)) {
                return false;
            }

            if(mb_strlen($npart, 'utf-8') > $maxNamePartLength){
                return false;
            }

            if ($i > $maxNameParts) {
                return false;
            } elseif ($i == 0) {
                $validName['firstname'] = $this->capitalize($npart);
            } elseif ($i == 1) {
                $validName['lastname'] = $this->capitalize($npart);
            } else {
                $validName['lastname'] .= ' ' . $this->capitalize($npart);
            }

            $i++;
        }

        return $validName;
    }

    /**
     * Return array with validated and formatted url on success, otherwise false
     * @param $url
     * @return bool|string
     */
    public function url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) === false ? false : strtolower($url);
    }

    /**
     * Capitalize string
     * @param $str
     * @return string
     */
    private function capitalize($str)
    {
        $str = mb_strtolower($str, 'utf-8');
        $fc = mb_strtoupper(mb_substr($str, 0, 1, 'utf-8'), 'utf-8');
        return $fc . mb_substr($str, 1, null, 'utf-8');
    }
}