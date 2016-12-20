<?php
namespace Webiik;

class Request
{
    private $values = [];

    /**
     * Special-case HTTP headers that are otherwise unidentifiable as HTTP headers.
     * Typically, HTTP headers in the $_SERVER array will be prefixed with
     * `HTTP_` or `X_`. These are not so we list them here for later reference.
     *
     * @var array
     */
    private $special = array(
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE',
    );

    /**
     * Store value inside Request
     * @param $value
     */
    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * Get value from Request
     * @param $key
     * @return bool
     */
    public function get($key)
    {
        return isset($this->values[$key]) ? $this->values[$key] : false;
    }

    /**
     * Return array of all HTTP headers from $_SERVER
     * @return array
     */
    public function getHttpHeaders()
    {
        $results = array();
        foreach ($_SERVER as $key => $value) {
            $key = strtoupper($key);
            if (strpos($key, 'X_') === 0 || strpos($key, 'HTTP_') === 0 || in_array($key, $this->special)) {
                if ($key === 'HTTP_CONTENT_LENGTH') {
                    continue;
                }
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Return value of given header or false when header does not exist
     * @param $headerName
     * @return bool|string
     */
    public function getHeader($headerName, $extened = true)
    {
        $prefixes = [
            '',
            'x-',
            'http-',
            'http-x-',
        ];

        foreach ($prefixes as $prefix) {

            for ($i = 0; $i < 3; $i++) {

                $header = $prefix . $headerName;

                if ($i == 1) {
                    $header = str_replace('-', '_', $header);
                }

                if ($i == 2) {
                    $header = str_replace('_', '-', $header);
                }

                // Original
                if (isset($_SERVER[$header])) {
                    return $_SERVER[$header];
                }

                // Upper case
                if (isset($_SERVER[strtoupper($header)])) {
                    return $_SERVER[strtoupper($header)];
                }

                // Uc words
                if (isset($_SERVER[ucwords(strtolower($header), '-_')])) {
                    return $_SERVER[ucwords(strtolower($header), '-_')];
                }

                // Lower case
                if (isset($_SERVER[strtolower($header)])) {
                    return $_SERVER[strtolower($header)];
                }
            }
        }

        return false;
    }

    /**
     * Return request method: GET, POST, ...
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Return current URI eg. /page1/
     * @return mixed
     */
    public function getUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Return current URL eg. http://localhost/page1/
     * @return string
     */
    public function getUrl()
    {
        return $this->getRootUrl() . $this->getUri();
    }

    /**
     * Return root URL eg. http://localhost
     * @return string
     */
    public function getRootUrl()
    {
        return $this->getScheme() . '://' . $this->getHostName();
    }

    /**
     * Return domain with subdomains eg. localhost, www.domain.com
     * @return mixed
     */
    public function getHostName()
    {
        return $_SERVER['SERVER_NAME'];
    }

    /**
     * Return address of the page which referred to the current page or false if there is no referring page.
     * @return mixed
     */
    public function getReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
    }

    /**
     * Return array of user IP(s). Only trusted value is REMOTE_ADDR
     * @link http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
     * @return array
     */
    public function getIp()
    {
        $ip = [];
        $keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = array_merge($ip, [$key => $_SERVER[$key]]);
            }
        }
        return $ip;
    }

    /**
     * Return user agent string
     * @return string
     */
    public function getAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Return request scheme: http or https
     * @return string
     */
    public function getScheme()
    {
        $scheme = 'http';
        if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $scheme = 'https';
        }
        return $scheme;
    }
}