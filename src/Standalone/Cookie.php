<?php
/**
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
namespace Webiik;

/**
 * Class Cookie - Provides functions to work with $_COOKIE
 * @package Webiik
 */
class Cookie
{
    /** @var Arr */
    private $arr;
    private $domain = '';
    private $uri = '/';
    private $secure = false;
    private $httponly = false;

    /**
     * Sessions constructor.
     * @param Arr $arr
     */
    public function __construct(Arr $arr)
    {
        $this->arr = $arr;
    }

    /**
     * Set (sub)domain where cookies are available
     * @param $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Set base URI from cookies are available
     * @param $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Set secure param associated with all cookies
     * @param $bool
     */
    public function setSecure($bool)
    {
        $this->secure = $bool;
    }

    /**
     * Set httponly param associated with all cookies
     * @param $bool
     */
    public function setHttponly($bool)
    {
        $this->httponly = $bool;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return bool
     */
    public function getSecure()
    {
        return $this->secure;
    }

    /**
     * @return bool
     */
    public function getHttponly()
    {
        return $this->httponly;
    }

    /**
     * Set cookie
     * @param $name
     * @param null $value
     * @param null $expire
     * @param null $uri
     * @param null $domain
     * @param null $secure
     * @param null $httponly
     */
    public function setCookie($name, $value = null, $expire = null, $uri = null, $domain = null, $secure = null, $httponly = null)
    {
        setcookie(
            $name,
            $value,
            $expire,
            $uri ? $uri : $this->getUri(),
            $domain ? $domain : $this->getDomain(),
            $secure ? $secure : $this->getSecure(),
            $httponly ? $httponly : $this->getHttponly()
        );
    }

    /**
     * Return cookie value or false if cookie does not exist
     * @param $name
     * @return string|bool
     */
    public function getCookie($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
    }

    /**
     * Delete cookie
     * @param string $name
     * @param null $uri
     * @param null $domain
     * @param null $secure
     * @param null $httponly
     */
    public function delCookie($name, $uri = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->setCookie($name, '', 1, $uri, $domain, $secure, $httponly);
        $this->setCookie($name, false, null, $uri, $domain, $secure, $httponly);
        unset($_COOKIE[$name]);
    }

    /**
     * Delete all cookies
     */
    public function delCookies()
    {
        if (isset($_COOKIE)) {
            foreach ($_COOKIE as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                $this->delCookie($name);
            }
        }
        $_COOKIE = [];
    }
}