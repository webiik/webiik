<?php
namespace Webiik;

/**
 * Class UrlFile
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class UrlFile
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
}