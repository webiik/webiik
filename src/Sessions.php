<?php
namespace Webiik;

// Todo: Rewrite it to be not static
class Sessions
{
    private static $config = [
        'cookieExpire' => null,
        'cookiePath' => null,
        'cookieDomain' => null,
        'cookieSecure' => null,
        'cookieHttponly' => null,
    ];

    /**
     * Add config lines. It uses array_merge so keys can be overwritten.
     * @param array $keyValueArray The key is the name and the value is the regex.
     */
    public static function setConfig($keyValueArray)
    {
        self::$config = array_merge(self::$config, $keyValueArray);
    }

    /**
     * Set cookie
     * @param $name
     * @param null $value
     * @param null $expire
     * @param null $path
     * @param null $domain
     * @param null $secure
     * @param null $httponly
     */
    public static function setCookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        if (is_null($expire)) $expire = self::$config['cookieExpire'];
        if (!is_null($expire)) $expire = strtotime($expire);
        if (!$path) $path = self::$config['cookiePath'];
        if (!$domain) $domain = self::$config['cookieDomain'];
        if (!$secure) $secure = self::$config['cookieSecure'];
        if (!$httponly) $httponly = self::$config['cookieHttponly'];

        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Return cookie value or false if cookie does not exist
     * @param $name
     * @return string|bool
     */
    public static function getCookie($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
    }

    /**
     * Delete cookie
     * @param $name
     */
    public static function delCookie($name)
    {
        $_COOKIE[$name] = false;
        unset($_COOKIE[$name]);
    }

    /**
     * Delete all cookies
     */
    public static function delAllCookies()
    {
        if (isset($_COOKIE)) {
            foreach ($_COOKIE as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                $_COOKIE[$name] = false;
                unset($_COOKIE[$name]);
            }
        }
    }

    /**
     * Set session
     * @param $name
     * @param null $value
     */
    public static function setSession($name, $value = null)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Return session value or false if session does not exist
     * @param $name
     * @return string|bool
     */
    public static function getSession($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : false;
    }

    /**
     * Delete session
     * @param $name
     */
    public static function delSession($name)
    {
        $_SESSION[$name] = false;
        unset($_SESSION[$name]);
    }

    /**
     * Delete all sessions
     */
    public static function delAllSessions()
    {
        if (isset($_SESSION)) {
            foreach ($_SESSION as $session) {
                $parts = explode('=', $session);
                $name = trim($parts[0]);
                $_SESSION[$name] = false;
                unset($_SESSION[$name]);
            }
        }
    }
}