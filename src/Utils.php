<?php
namespace Webiik;

class Utils
{
    /**
     * On success return URL path without filename, query string and trailing slash
     * If url is not valid return false
     * @param $url
     * @return string|bool
     */
    public function getUrlPath($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) return false;

        $urlParts = parse_url($url);

        $url = isset($urlParts['path']) ? $urlParts['path'] : false;

        $uri = $urlParts['scheme'] . '://' . $urlParts['host'];

        // Process path
        if ($url) {
            $url = pathinfo($url);

            if (isset($url['extension'])) {
                $url = $url['dirname'];
            } else {
                $url = $url['basename'] ? $url['dirname'] . '/' . $url['basename'] : $url['dirname'];
            }
        }

        return rtrim($uri . $url, '/');
    }

    /**
     * On success return filename from URL
     * If URL is not valid or does not contain file return false
     * @param $url
     * @return string|bool
     */
    public function getUrlFile($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) return false;

        $urlParts = parse_url($url);

        if (isset($urlParts['path'])) {
            preg_match('/\/([^\/]*\.\w{1,}$)/', $urlParts['path'], $match);
        }

        return isset($match[1]) ? $match[1] : false;
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