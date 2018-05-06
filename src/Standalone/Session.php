<?php
/**
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
namespace Webiik;

/**
 * Class Session - Provides functions to work securely with $_SESSION
 * @package Webiik
 */
class Session
{
    /** @var Arr */
    private $arr;

    /** @var Cookie */
    private $cookie;

    private $sessionName = 'PHPSESSID';
    private $sessionDir = '';
    private $sessionCookieLifetime = 0;
    private $sessionGcLifetime = 1440;

    /**
     * Sessions constructor.
     * @param Arr $arr
     */
    public function __construct(Arr $arr, Cookie $cookie)
    {
        $this->arr = $arr;
        $this->cookie = $cookie;
    }

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
     * Set max time on how long will be session stored in the browser
     * Default value is set to 0, it means till browser is closed
     * @param $sec
     */
    public function setSessionCookieLifetime($sec)
    {
        $this->sessionCookieLifetime = $sec;
    }

    /**
     * Set max time on how long will be an unused PHP session kept alive
     * Default value is set to 1440
     * @param $sec
     */
    public function setSessionGcLifetime($sec)
    {
        $this->sessionGcLifetime = $sec;
    }

    /**
     * Regenerate session id and delete old session
     */
    public function sessionRegenerateId()
    {
        $this->sessionStart();
        session_regenerate_id(true);
    }

    /**
     * Add value into session
     * @param $key
     * @param $value
     */
    public function setToSession($key, $value)
    {
        $this->sessionStart();
        $this->arr->set($_SESSION, $key, $value);
    }

    /**
     * Add value into session
     * @param $key
     * @param $value
     */
    public function addToSession($key, $value)
    {
        $this->sessionStart();
        $this->arr->add($_SESSION, $key, $value);
    }

    /**
     * Return session value or false if session does not exist
     * @param $key
     * @return string|bool
     */
    public function getFromSession($key)
    {
        $this->sessionStart();
        return $this->arr->get($_SESSION, $key);
    }

    /**
     * Return all session values
     * @return mixed
     */
    public function getAllSessions()
    {
        $this->sessionStart();
        return $_SESSION;
    }

    /**
     * Delete value from session
     * @param $key
     */
    public function delFromSession($key)
    {
        $this->sessionStart();
        $this->arr->delete($_SESSION, $key);
    }

    /**
     * Delete all values in session
     */
    public function dellAllFromSession()
    {
        $this->sessionStart();
        $_SESSION = [];
    }

    /**
     * Delete session
     */
    public function sessionDestroy()
    {
        $this->sessionStart();
        $this->dellAllFromSession();
        $this->cookie->delCookie(session_name());
        session_destroy();
    }

    /**
     * Start session if is not started and set session parameters and add basic values
     * Delete session if is expired or if is suspicious
     * @return bool
     */
    private function sessionStart()
    {
        if (session_status() == PHP_SESSION_NONE) {

            ini_set('session.gc_maxlifetime', $this->sessionGcLifetime);

            if ($this->sessionDir) session_save_path($this->sessionDir);

            session_name($this->sessionName);

            session_set_cookie_params(
                $this->sessionCookieLifetime,
                $this->cookie->getUri(),
                $this->cookie->getDomain(),
                $this->cookie->getSecure(),
                $this->cookie->getHttponly()
            );

            session_start();

            $this->addBasicSessionValues();

            if ($this->isSessionSuspicious()) {
                $this->sessionDestroy();
                return false;
            }
        }

        return true;
    }

    /**
     * Add basic values we use to handle every session
     */
    private function addBasicSessionValues()
    {
        if (!$this->getFromSession('ip')) {
            $this->setToSession('ip', $_SERVER['REMOTE_ADDR']);
        }

        if (!$this->getFromSession('agent') && isset($_SERVER['HTTP_USER_AGENT'])) {
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

        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;

        if ($this->getFromSession('agent') != $agent) {
            return true;
        }

        return false;
    }
}