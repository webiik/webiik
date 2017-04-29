<?php
/**
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
namespace Webiik;

/**
 * Class Request - Provides functions to work with incoming HTTP request
 * @package Webiik
 */
class Request
{
    /**
     * Return query string eg. city=Prague&country=CzechRepublic
     * @return mixed
     */
    public function getQueryString()
    {
        return $_SERVER['QUERY_STRING'];
    }

    /**
     * Return current URI eg. /page1/
     * @param bool $withQueryString
     * @return mixed
     */
    public function getUri($withQueryString = false)
    {
        return $withQueryString ? $_SERVER['REQUEST_URI'] . '?' . $this->getQueryString() : $_SERVER['REQUEST_URI'];
    }

    /**
     * Return current URL eg. http://localhost/webiik/sub-page
     * @param bool $withQueryString
     * @return string
     */
    public function getUrl($withQueryString = false)
    {
        return $this->getHostUrl() . $this->getUri($withQueryString);
    }

    /**
     * Get directory of executing script eg. /webiik
     * @return string
     */
    public function getWebRootPath()
    {
        return dirname($_SERVER['SCRIPT_NAME']);
    }

    /**
     * Return web root eg. http://localhost/webiik
     * @return string
     */
    public function getWebRootUrl()
    {
        $pageURL = $this->getHostUrl();
        $pageURL .= rtrim($this->getWebRootPath(), '/');
        return $pageURL;
    }

    /**
     * Return host root with current scheme eg. http://localhost
     * @return string
     */
    public function getHostUrl()
    {
        return $this->getScheme() . '://' . $this->getHostName();
    }

    /**
     * Return domain with subdomains eg. localhost, www.domain.com
     * @return string
     */
    public function getHostName()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
    }

    /**
     * Return credible IP value.
     * @return mixed
     */
    public function getIP()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Return array of user IP(s). REMOTE_ADDR is only credible value.
     * @link http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
     * @return array
     */
    public function getIPs()
    {
        $ip = [];
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip[$key] = $_SERVER[$key];
            }
        }
        return $ip;
    }

    /**
     * Return HTTP protocol version
     * @return string
     */
    public function getProtocolVersion()
    {
        preg_match('/\/(.+)/', $_SERVER['SERVER_PROTOCOL'], $matches);
        return $matches[1];
    }

    /**
     * Return value(s) from $_GET (case insensitive) or $default
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function getGet($name = null, $default = null)
    {
        return $name ? $this->valueSearch($_GET, $name, $default) : $_GET;
    }

    /**
     * Return value(s) from $_POST (case insensitive) or $default
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function getPost($name = null, $default = null)
    {
        return $name ? $this->valueSearch($_POST, $name, $default) : $_POST;
    }

    /**
     * Return value(s) from $_SERVER (case insensitive) or $default
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function getHeader($name = null, $default = null)
    {
        return $name ? $this->valueSearch($_SERVER, $name, $default) : $_SERVER;
    }

    /**
     * Return value(s) from $_COOKIE (case insensitive) or $default
     * @param null $name
     * @param null $default
     * @return mixed
     */
    public function getCookie($name = null, $default = null)
    {
        return $name ? $this->valueSearch($_COOKIE, $name, $default) : $_COOKIE;
    }

    /**
     * Return user agent string.
     * @return string
     */
    public function getAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Return referring URL or empty string if referrer isn't set.
     * @return string
     */
    public function getReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    /**
     * Return HTTP method in uppercase.
     * @return string
     */
    public function getMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Check HTTP method (case insensitive).
     * @param $method
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() == strtoupper($method) ? true : false;
    }

    /**
     * Check if request is over HTTPS or not.
     * @return bool
     */
    public function isSecured()
    {
        return isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? true : false;
    }

    /**
     * Check if request is Ajax (untrustworthy)
     * @return bool
     */
    public function isAjax()
    {
        return $this->getHeader('HTTP_X_REQUESTED_WITH') || $this->getHeader('HTTP-X-REQUESTED-WITH') ? true : false;
    }

    /**
     * Return request scheme: http or https.
     * @return string
     */
    private function getScheme()
    {
        return $this->isSecured() ? 'https' : 'http';
    }

    /**
     * Search value in Array (case insensitive)
     * @param array $arr
     * @param $name
     * @param $default
     * @return mixed
     */
    private function valueSearch(array $arr, $name, $default)
    {
        $arr = array_change_key_case($arr, CASE_LOWER);
        $name = strtolower($name);
        return isset($arr[$name]) ? $arr[$name] : $default;
    }
}