<?php
namespace Webiik;

class Request
{
    /**
     * Special-case HTTP headers that are otherwise unidentifiable as HTTP headers.
     * Typically, HTTP headers in the $_SERVER array will be prefixed with
     * `HTTP_` or `X_`. These are not so we list them here for later reference.
     *
     * @var array
     */
    protected $special = array(
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE',
    );

    /**
     * Return array of all HTTP headers from $_SERVER
     * @return array
     */
    public function getReqHttpHeaders()
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
    public function getReqHeader($headerName)
    {
        return isset($_SERVER[$headerName]) ? $_SERVER[$headerName] : false;
    }

    /**
     * Return request method: GET, POST, ...
     * @return string
     */
    public function getReqMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Return current URI eg. /page1/
     * @return mixed
     */
    public function getReqUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Return current URL eg. http://localhost/page1/
     * @return string
     */
    public function getReqUrl()
    {
        return $this->getRootUrl().$this->getReqUri();
    }

    /**
     * Return address of the page which referred to the current page or false if there is no referring page.
     * @return mixed
     */
    public function getReqReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
    }

    /**
     * Return request scheme: http or https
     * @return string
     */
    public function getReqScheme()
    {
        $scheme = 'http';
        if(isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
            $scheme = 'https';
        }
        return $scheme;
    }

    /**
     * Return array of user IP(s). Only trusted value is REMOTE_ADDR
     * @link http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
     * @return array
     */
    public function getReqIp()
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
    public function getReqAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * @return bool
     */
    public function isReqSecured()
    {
        return $this->getReqScheme() == 'https' ? true : false;
    }

    /**
     * Check request for given http method
     * @param $httpMethod
     * @return bool
     */
    public function isReq($httpMethod)
    {
        return $this->getReqMethod() == $httpMethod ? true : false;
    }

    // Host functions

    /**
     * Return array of server IP(s)
     * @return array
     */
    public function getHostIp()
    {
        return gethostbyname(gethostname());
    }

    /**
     * Return server port number
     * @return mixed
     */
    public function getHostPort()
    {
        return $_SERVER['SERVER_PORT'];
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
     * Return true if host supports secure connection, otherwise false
     * @return bool
     */
    public function isHostSecured()
    {
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? true : false;
    }

    // Other functions

    /**
     * Return root URL eg. http://localhost
     * @return string
     */
    public function getRootUrl()
    {
        return $this->getReqScheme().'://'.$this->getHostName();
    }

}