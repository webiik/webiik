<?php
namespace Webiik;

class Sessions
{
    private $sessionName = 'PHPSESSID';
    private $sessionDir = '';
    private $sessionLifetime = 0;

    private $domain = '';
    private $uri = '/';
    private $secure = false;
    private $httponly = false;

    /**
     * Set session name
     * @param $name
     */
    public function setSessionName($name)
    {
        $this->sessionName = $name;
    }

    /**
     * Set dir on server where sessions are stored
     * @param $path
     */
    public function setSessionDir($path)
    {
        $this->sessionDir = $path;
    }

    /**
     * Set max time in sec how long will be session stored on the server and session cookie in the browser
     * Default value is set to 0, it means till session is valid
     * @param $sec
     */
    public function setSessionSystemLifetime($sec)
    {
        $this->sessionLifetime = $sec;
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
        if ($expire) $expire = strtotime($expire);
        setcookie($name, $value, $expire, $this->uri($uri), $this->domain($domain), $this->secure($secure), $this->httponly($httponly));
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

    /**
     * Start session if is not started and set session parameters and add basic values
     * Delete session if is expired or if is suspicious
     * @param int $lifetime
     * @param string $uri
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return bool
     */
    public function sessionStart($lifetime = 0, $uri = '/', $domain = '', $secure = false, $httponly = false)
    {
        if (session_status() == PHP_SESSION_NONE) {

            $lifetime = $this->lifetime($lifetime);

            ini_set('session.gc_maxlifetime', $lifetime);

            if ($this->sessionDir) session_save_path($this->sessionDir);

            session_name($this->sessionName);

            session_set_cookie_params($lifetime, $uri, $domain, $secure, $httponly);

            session_start();

            $this->addBasicSessionValues();

            if ($this->isSessionSuspicious()) {
                $this->sessionDestroy();
                return false;
            }

            if (rand(1, 100) <= 5) {
                $this->sessionRegenerateId();
            }
        }

        return true;
    }

    /**
     * Regenerate session id and delete old session
     */
    public function sessionRegenerateId()
    {
        session_regenerate_id(true);
    }

    /**
     * Add value into session
     * @param $key
     * @param $value
     */
    public function setToSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Return session value or false if session does not exist
     * @param $key
     * @return string|bool
     */
    public function getFromSession($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }

    /**
     * Delete value from session
     * @param $key
     */
    public function delFromSession($key = null)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Delete all values in session
     */
    public function dellAllFromSession()
    {
        $_SESSION = [];
    }

    /**
     * Delete session
     */
    public function sessionDestroy()
    {
        $this->sessionStart();
        $this->dellAllFromSession();
        $this->delCookie(session_name());
        session_destroy();
    }

    /**
     * @param $lifetime
     * @return null
     */
    private function lifetime($lifetime)
    {
        return $lifetime ? $lifetime : $this->sessionLifetime;
    }

    /**
     * @param $uri
     * @return null
     */
    private function uri($uri)
    {
        return $uri ? $uri : $this->uri;
    }

    /**
     * @param $domain
     * @return null
     */
    private function domain($domain)
    {
        return $domain ? $domain : $this->domain;
    }

    /**
     * @param $secure
     * @return null
     */
    private function secure($secure)
    {
        return $secure ? $secure : $this->secure;
    }

    /**
     * @param $httponly
     * @return null
     */
    private function httponly($httponly)
    {
        return $httponly ? $httponly : $this->httponly;
    }

    /**
     * Add basic values we use to handle every session
     */
    private function addBasicSessionValues()
    {
        if (!$this->getFromSession('ip')) {
            $this->setToSession('ip', $_SERVER['REMOTE_ADDR']);
        }

        if (!$this->getFromSession('agent')) {
            $this->setToSession('agent', $_SERVER['HTTP_USER_AGENT']);
        }
    }

    /**
     * Session hijacking and session fixation protection.
     * If user agent or IP was changed during session lifetime,
     * then session is suspicious.
     */
    private function isSessionSuspicious()
    {
        if ($this->getFromSession('ip') != $_SERVER['REMOTE_ADDR']) {
            return true;
        }

        if ($this->getFromSession('agent') != $_SERVER['HTTP_USER_AGENT']) {
            return true;
        }

        return false;
    }
}