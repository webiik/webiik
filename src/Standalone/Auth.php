<?php

namespace Webiik;

/**
 * Class Auth
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Auth
{
    /**
     * @var Cookie
     */
    private $cookie;

    /**
     * @var Session
     */
    private $session;


    /**
     * Closure MUST have the following parameters: function ($uid, $token, $expirationTs) {}
     * Closure MUST save these parameters as serialised array.
     * Closure MUST return 6 chars long unique string on success, otherwise FALSE.
     * Look at permanentLoginFileStore() method to get inspired.
     * @var \Closure
     */
    private $permanentRecordStoreFn;

    /**
     * Closure MUST have the following parameter: function ($selector) {}
     * Closure MUST return content of permanent record on success, otherwise FALSE.
     * Look at permanentLoginFileGet() method to get inspired.
     * @var \Closure
     */
    private $permanentRecordGetFn;

    /**
     * Closure MUST have the following parameter: function ($selector) {}
     * Closure MUST return TRUE on success, otherwise FALSE.
     * Look at permanentLoginFileDelete() method to get inspired.
     * @var \Closure
     */
    private $permanentRecordDeleteFn;

    /**
     * Closure MUST delete all expired records.
     * Look at permanentLoginFileDeleteAllExpired() method to get inspired.
     * @var \Closure
     */
    private $permanentRecordDeleteAllExpiredFn;

    /**
     * @var Token
     */
    protected $token;

    /**
     * Auth configuration
     * @var $config
     */
    private $config = [
        // Login session name
        'sessionName' => 'logged',
        // Auto logout when user is not active for given time
        'autoLogoutTime' => 0,
        // Permanent login cookie name
        'permanentCookieName' => 'pc',
        // How many hours can be the user logged in
        'permanentCookieExpiration' => 0,
        // Automatically delete expired permanent files with 5% chance during every isLogged call
        'autoDeleteExpiredPermanentRecords' => 5,
    ];

    /**
     * Auth constructor.
     * @param Cookie $cookie
     * @param Session $session
     * @param Token $token
     */
    public function __construct(Cookie $cookie, Session $session, Token $token)
    {
        $this->cookie = $cookie;
        $this->session = $session;
        $this->token = $token;
        $this->permanentRecordStoreFn = $this->permanentLoginFileStore();
        $this->permanentRecordGetFn = $this->permanentLoginFileGet();
        $this->permanentRecordDeleteFn = $this->permanentLoginFileDelete();
        $this->permanentRecordDeleteAllExpiredFn = $this->permanentLoginFileDeleteAllExpired();
    }

    /**
     * Set custom permanent record handling functions
     * @param \Closure $storeFn
     * @param \Closure $getFn
     * @param \Closure $delFn
     * @param \Closure $delExpiredFn
     */
    public function setCustomRecordFns($storeFn, $getFn, $delFn, $delExpiredFn) {
        $this->permanentRecordStoreFn = $storeFn;
        $this->permanentRecordGetFn = $getFn;
        $this->permanentRecordDeleteFn = $delFn;
        $this->permanentRecordDeleteAllExpiredFn = $delExpiredFn;
    }

    /**
     * Set suffix
     * Note: This is handy if you need to distinguish login session and cookie by language or anything else
     * @param string $str
     */
    public function setAuthSuffix($str)
    {
        $this->config['suffix'] = strtolower(ucfirst($str));
        $this->config['sessionName'] .= $this->config['suffix'];
        $this->config['permanentCookieName'] .= $this->config['suffix'];
    }

    /**
     * Set login session name
     * @param string $string
     */
    public function setSessionName($string)
    {
        $this->config['sessionName'] = isset($this->config['suffix']) ? $string . $this->config['suffix'] : $string;
    }

    /**
     * Set permanent cookie name
     * @param string $string
     */
    public function setPermanentCookieName($string)
    {
        $this->config['permanentCookieName'] = isset($this->config['suffix']) ? $string . $this->config['suffix'] : $string;
    }

    /**
     * Set how many hours can be the user permanently logged in
     * @param int $sec
     */
    public function setPermanentCookieExpirationTime($sec)
    {
        $this->config['permanentCookieExpiration'] = $sec;
    }

    /**
     * Set permanent files dir
     * @param string $path
     */
    public function setPermanentFilesDir($path)
    {
        $this->config['permanentFilesDir'] = rtrim($path, '/') . '/';
    }

    /**
     * Set % chance to delete expired permanent login file during every isLogged call
     * @param int $percent
     */
    public function setAutoDeleteExpiredPermanentRecords($percent)
    {
        $this->config['autoDeleteExpiredPermanentRecords'] = $percent;
    }

    /**
     * Set auto logout when user is not active for given time
     * @param int $sec
     */
    public function setAutoLogoutTime($sec)
    {
        $this->config['autoLogoutTime'] = $sec;
    }

    /**
     * Create login session with given user id, eventually do steps necessary for permanent login
     * @param int $uid
     * @param bool $permanent
     */
    public function login($uid, $permanent = false)
    {
        $this->session->sessionRegenerateId();
        $this->session->setToSession($this->config['sessionName'], $uid);

        // Auto logout
        if ($this->config['autoLogoutTime'] > 0) {
            $this->session->setToSession($this->config['sessionName'] . 'Ts', $_SERVER['REQUEST_TIME']);
        }

        // Permanent login
        if ($permanent && $this->config['permanentCookieExpiration'] > 0) {
            $this->permanentLoginCreate($uid);
        }
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * If permanent login is active
     * Return user id on success otherwise false
     * @return bool|int
     */
    public function isLogged()
    {
        $uid = $this->session->getFromSession($this->config['sessionName']);

        // If we cannot obtain uid from login session,
        // we will check permanent login option to get uid
        if (
            !$uid
            && $this->config['permanentCookieExpiration'] > 0
            && $permanentLoginFileData = $this->permanentLoginCheck()
        ) {
            $uid = $permanentLoginFileData['uid'];
            $this->login($uid);

            // Delete expired permanent files
            $this->deleteExpiredPermanentRecords();

        }

        // If user is logged in, check if user wasn't inactive for too long
        if ($uid && $this->config['autoLogoutTime'] > 0) {

            $lastLoginCheckTs = $this->session->getFromSession($this->config['sessionName'] . 'Ts');

            if (!$lastLoginCheckTs || ($lastLoginCheckTs + $this->config['autoLogoutTime'] < $_SERVER['REQUEST_TIME'])) {
                $this->logout();
                return false;
            }

            $this->session->setToSession($this->config['sessionName'] . 'Ts', $_SERVER['REQUEST_TIME']);
        }

        return $uid;
    }

    /**
     *  Delete all logged indicators and for sure destroy session
     */
    public function logout()
    {
        $this->permanentLoginRemove();
        $this->session->delFromSession($this->config['sessionName']);
        $this->session->delFromSession($this->config['sessionName'] . 'Ts');
        $this->session->sessionDestroy();
    }

    /**
     * Return referrer if exists or false
     * @return bool
     */
    public function getReferrer()
    {
        $referrer = false;

        if (isset($_POST['ref'])) {
            $referrer = $_POST['ref'];
        } elseif (isset($_GET['ref'])) {
            $referrer = $_GET['ref'];
        }

        return $referrer;
    }

    /**
     * Redirect user to specified $url
     * @param string $url
     * @param bool $safe - Redirect only to URLs from current domain
     * @return bool
     */
    public function redirect($url, $safe = true)
    {
        if ($safe && !$this->isUrlFromCurrentDomain($url)) {
            return false;
        }

        header('HTTP/1.1 302 Found');
        header('Location:' . $url);
        exit;
    }

    // permanentLoginCreateFile -> permanentLoginCreate
    private function permanentLoginCreate($uid)
    {
        $expirationTs = time() + $this->config['permanentCookieExpiration'];
        $cookieName = $this->config['permanentCookieName'];
        $token = $this->token->generate(16);

        $storeFn = $this->permanentRecordStoreFn;

        if ($selector = $storeFn($uid, $token, $expirationTs)) {
            $this->cookie->setCookie($cookieName, $selector . ':' . $token, $expirationTs);
        }
    }

    /**
     * Validate permanent login cookie
     * @return array|bool
     */
    private function validatePermanentCookie()
    {
        $cookieName = $this->config['permanentCookieName'];

        // Does permanent cookie exist?
        if (!$cookieVal = $this->cookie->getCookie($cookieName)) {
            // Err: Permanent cookie does not exists or is expired
            return false;
        }

        // Does permanent cookie contain selector and token?
        $cookieVal = explode(':', $cookieVal);
        if (!isset($cookieVal[0]) || !isset($cookieVal[1])) {
            // Err: Selector or token does not exist
            $this->cookie->delCookie($cookieName);
            return false;
        }

        // Does selector and token measure 12 and 32 characters?
        if (strlen($cookieVal[0]) != 12 || strlen($cookieVal[1]) != 32) {
            // Err: Selector or token has invalid length
            $this->cookie->delCookie($cookieName);
            return false;
        }

        // Get selector and token from permanent cookie
        return [
            'selector' => $cookieVal[0],
            'token' => $cookieVal[1]
        ];
    }

    /**
     * @param $selector
     * @return bool|mixed
     */
    private function validatePermanentRecord($selector)
    {
        // Does exist permanent file with name of selector?
        $getFn = $this->permanentRecordGetFn;
        if(!$data = $getFn($selector)) {
            return false;
        }

        // Get info array from permanent file
        $data = unserialize($data);
        if (!isset($data['uid']) || !isset($data['selector']) || !isset($data['token'])) {
            // Err: Permanent file does not contain required data
            $deleteFn = $this->permanentRecordDeleteFn;
            $deleteFn($selector);
            return false;
        }

        // Does selector and token measure 12 and 64 characters?
        if (strlen($data['selector']) != 12 || strlen($data['token']) != 64) {
            // Err: Selector or token has invalid length
            $deleteFn = $this->permanentRecordDeleteFn;
            $deleteFn($selector);
            return false;
        }

        return $data;
    }

    /**
     * If permanent login cookie and file are valid return user id, otherwise delete them
     * @return bool|array
     */
    private function permanentLoginCheck()
    {
        $cookieName = $this->config['permanentCookieName'];

        // Validate permanent login cookie
        if (!$cookieData = $this->validatePermanentCookie()) {
            // Err: Cookie isn't valid
            return false;
        }

        // Validate permament login reocord
        if (!$fileData = $this->validatePermanentRecord($cookieData['selector'])) {
            // Err: File isn't valid
            $this->cookie->delCookie($cookieName);
            return false;
        }

        // Is token from cookie same like token from file?
        if (hash('sha256', $cookieData['token']) !== $fileData['token']) {
            // Err: Token doesn't match
            $this->cookie->delCookie($cookieName);
            $deleteFn = $this->permanentRecordDeleteFn;
            $deleteFn($cookieData['selector']);
            return false;
        }

        return $fileData;
    }

    /**
     * Delete permanent login cookie and file
     */
    private function permanentLoginRemove()
    {
        if ($cookieVal = $this->validatePermanentCookie()) {
            $this->cookie->delCookie($this->config['permanentCookieName']);
            $deleteFn = $this->permanentRecordDeleteFn;
            $deleteFn($cookieVal['selector']);
        }
    }

    /**
     * Delete expired permanent login files by configured percentage chance to delete
     */
    private function deleteExpiredPermanentRecords()
    {
        if (rand(1, 100) <= $this->config['autoDeleteExpiredPermanentRecords']) {
            $delExpiredFn = $this->permanentRecordDeleteAllExpiredFn;
            $delExpiredFn();
        }
    }

    /**
     * Check if url comes from current domain or not
     * @param $url
     * @return bool
     */
    private function isUrlFromCurrentDomain($url)
    {
        $parsedReferrerUrl = parse_url($url);

        $scheme = isset($parsedReferrerUrl['scheme']) ? $parsedReferrerUrl['scheme'] : false;
        $host = isset($parsedReferrerUrl['host']) ? $parsedReferrerUrl['host'] : false;

        if ($scheme && $host == $_SERVER['SERVER_NAME']) {
            return true;
        }

        return false;
    }

    // -------------------------------------------------------------------------------
    // Permanent record manipulation functions

    /**
     * Create permanent login file and return its selector on success, otherwise FALSE
     * @return \Closure
     */
    private function permanentLoginFileStore()
    {
        $fn = function ($uid, $token, $expirationTs, $i = 0) use (&$fn) {
            $selector = $this->token->generate(6);
            $file = $this->config['permanentFilesDir'] . $selector;

            if (file_exists($file)) {
                $i++;
                if ($i < 10) {
                    $fn($uid, $token, $expirationTs, $i);
                } else {
                    return false;
                }
            }

            $data = [
                'uid' => $uid,
                'ts' => $expirationTs,
                'token' => hash('sha256', $token)
            ];

            return file_put_contents($file, serialize($data)) ? $selector : false;
        };

        return $fn;
    }

    /**
     * Get permanent login file and return its content on success, otherwise FALSE
     * @return \Closure
     */
    private function permanentLoginFileGet()
    {
        $fn = function ($selector) {
            $file = $this->config['permanentFilesDir'] . $selector;
            return file_exists($file) ? file_get_contents($file) : false;
        };

        return $fn;
    }

    /**
     * Delete permanent login file and return TRUE on success, otherwise FALSE
     * @return \Closure
     */
    private function permanentLoginFileDelete()
    {
        $fn = function ($selector) {
            $file = $this->config['permanentFilesDir'] . $selector;
            return @unlink($file) ? true : false;
        };

        return $fn;
    }

    /**
     * Delete all expired permanent login files
     * @return \Closure
     */
    private function permanentLoginFileDeleteAllExpired()
    {
        $fn = function () {
            foreach (new \DirectoryIterator($this->config['permanentFilesDir']) as $item) {
                if ($item->isFile()) {
                    $fileExpirationTime = $_SERVER['REQUEST_TIME'] - $this->config['permanentCookieExpiration'];
                    if ($fileExpirationTime > $item->getMTime()) {
                        unlink($item->getPathname());
                    }
                }
            }
        };

        return $fn;
    }
}