<?php
namespace Webiik;

class Format
{
    /**
     * Return validated and formatted email on success, otherwise false
     * @param $email
     * @return bool|string
     */
    public function email($email)
    {
        $pattern = '/^[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\@[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\.[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~\.]{2,63}$/';

        preg_match($pattern, $email, $match);

        if (empty($match)) {
            return false;
        }

        return $email;
    }

    /**
     * Return array with validated and formatted firstname and lastname(s) on success, otherwise false
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

            if (mb_strlen($npart, 'utf-8') > $maxNamePartLength) {
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
     * On success return formatted $url
     * On error return false  
     * @param $url
     * @return bool|string
     */
    public function url($url)
    {
        $pu = parse_url($url);
        $url = false;

        if ($pu) {

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