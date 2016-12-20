<?php
namespace Webiik;

class Auth
{
    /**
     * @var Sessions
     */
    private $sessions;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var Attempts
     */
    private $attempts;

    /**
     * Auth configuration
     * @var $config
     */
    private $config = [
        // How many days can be the user logged in
        'permanent' => 0,
        // Require account activation or not
        'withActivation' => false,
        // Allow 60 login attempts per 1 hour from same ip
        'login' => [60, 3600],
        // Allow 120 is logged attempts per 60s from same ip
        'is-logged' => [120, 60],
        // Allow 30 sign-up attempts per 1 hour from same ip
        'signup' => [30, 3600],
        // Allow 8 activation requests/confirmations per 1 hour from same ip
        'activation-request' => [8, 3600],
        'activation-confirmation' => [8, 3600],
        // Allow 4 password renewal requests/confirmations per 24 hours from same ip
        'password-request' => [4, 86400],
        'password-confirmation' => [4, 86400],
        // Allow activate account or confirm password renewal within 24 hours
        // older records will be deleted
        'confirmationTime' => 86400,
        // Permanent login cookie name
        'cookieName' => 'PC',
        // Password salt
        'salt' => 'r489cjd3Xhed',
        // Account service URIs
        'uri' => [],
    ];

    /**
     * Auth constructor.
     * @param Sessions $sessions
     * @param Connection $connection
     * @param Token $token
     * @param Attempts $attempts
     */
    public function __construct(Sessions $sessions, Connection $connection, Token $token, Attempts $attempts)
    {
        $this->sessions = $sessions;
        $this->connection = $connection;
        $this->token = $token;
        $this->attempts = $attempts;
    }

    /**
     * Configure salt
     * @param string $string
     */
    public function confSalt($string)
    {
        $this->config['salt'] = $string;
    }

    /**
     * Configure permanent cookie name
     * @param string $string
     */
    public function confCookieName($string)
    {
        $this->config['cookieName'] = $string;
    }

    /**
     * Configure how many days can permanently logged user be logged in
     * @param int $days
     */
    public function confPermanent($days)
    {
        $this->config['permanent'] = $days;
    }

    /**
     * Configure if sign-up needs activation or not
     * @param bool $bool
     */
    public function confWithActivation($bool)
    {
        $this->config['withActivation'] = $bool;
    }

    /**
     * Configure account service URIs
     * If you want to use redirection by referrer in login, signup etc., you will configure this
     * If URIs are set, then user will not be redirected to referrer equal to account service URIs
     * Very helpful to preventing redirects like login->logout, login->signup etc.
     * URI is URL without scheme eg. 'webiik.com/login/'
     * @param array $uris
     */
    public function confURIs(array $uris)
    {
        $this->config['uri'] = $uris;
    }

    /**
     * Configure get user from DB (login) attempts count per time period from same IP
     * @param int $count
     * @param int $sec
     */
    public function confUserGetAttempts($count, $sec)
    {
        $this->config['login'] = [$count, $sec];
    }

    /**
     * Configure set user to DB (sign-up) attempts count per time period from same IP
     * @param int $count
     * @param int $sec
     */
    public function confUserSetAttempts($count, $sec)
    {
        $this->config['signup'] = [$count, $sec];
    }

    /**
     * Configure password renewal attempts count per time period from same IP
     * @param int $count
     * @param int $sec
     */
    public function confUserPswdAttempts($count, $sec)
    {
        // Attempts for password change request and password change confirmation
        $this->config['password-request'] = [$count, $sec];
        $this->config['password-confirmation'] = [$count, $sec];

    }

    /**
     * Configure activation attempts count per time period from same IP
     * @param int $count
     * @param int $sec
     */
    public function confUserActivationAttempts($count, $sec)
    {
        // Attempts for account activation
        $this->config['activation-request'] = [$count, $sec];
        $this->config['activation-confirmation'] = [$count, $sec];

    }

    /**
     * Configure how much time has user to confirm activation or password renewal
     * @param int $sec
     */
    public function confConfirmationTime($sec)
    {
        $this->config['confirmationTime'] = $sec;
    }

    /**
     * Create logged session with given user id, eventually do steps necessary for permanent login
     * @param int $uid
     */
    public function userLogin($uid)
    {
        $this->sessions->sessionRegenerateId();
        $this->sessions->setToSession('logged', $uid);

        if ($this->config['permanent'] > 0) {
            $this->userLoginPermanent($uid);
        }
    }

    /**
     *  Delete all logged indicators and for sure destroy session
     */
    public function userLogout()
    {
        $this->userLogoutPermanent();
        $this->sessions->delFromSession('logged');
        $this->sessions->sessionDestroy();
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * Return user id on success otherwise false
     * @return bool|int
     */
    public function isUserLogged()
    {
        $uid = $this->sessions->getFromSession('logged');

        if ($uid) {
            return $uid;
        }

        if ($this->config['permanent'] > 0) {
            $uid = $this->isUserPermanentlyLogged();
        }

        if ($uid) {
            $this->userLogin($uid);
        }

        return $uid;
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
     * If referrer is allowed and exists, redirect user to referrer instead of $url
     * @param string $url
     * @param bool $allowReferrer
     */
    public function redirect($url, $allowReferrer = false)
    {
        if ($allowReferrer) {

            // Do we have some referrer?
            $referrer = $this->getReferrer();

            if ($referrer) {

                // Re-format referrer URL
                $parsedReferrerUrl = parse_url($referrer);

                $scheme = isset($parsedReferrerUrl['scheme']) ? $parsedReferrerUrl['scheme'] : false;
                $host = isset($parsedReferrerUrl['host']) ? $parsedReferrerUrl['host'] : false;
                $path = isset($parsedReferrerUrl['path']) ? $parsedReferrerUrl['path'] : '';

                // Is referrer from current domain?
                if ($scheme && $host == $_SERVER['SERVER_NAME']) {

                    $referrerUri = $host . $path;

                    // Is referrer different than account service pages?
                    foreach ($this->config['uri'] as $uri) {

                        if ($uri != $referrerUri) {
                            $url = $referrer;
                            break;
                        }
                    }
                }
            }
        }

        header('HTTP/1.1 302 Found');
        header('Location:' . $url);
        exit;
    }

    /**
     * Return user id if user can do the action, otherwise false
     * @param string $action
     * @return mixed
     */
    public function userCan($action)
    {
        $uid = $this->isUserLogged();

        if (!$uid) {
            return false;
        }

        // Check if user can do the action

        $pdo = $this->connection->connect();

        // Get available user actions
        $q = $pdo->prepare('
            SELECT action 
            FROM auth_actions 
            WHERE id IN 
            (SELECT action_id FROM auth_roles_actions WHERE role_id = (SELECT role_id FROM auth_users WHERE id = ?))
        ');
        $q->execute([$uid]);
        $r = $q->fetchAll();

        // Check if user can do the action
        foreach ($r as $row) {
            if ($row['action'] == $action) {
                return $uid;
            }
        }

        return false;
    }

    /**
     * Set user in to database
     *
     * On success return array:
     * without activation ['err' => false, 'uid' => int, 'msg' => string]
     * with activation ['err' => false, 'uid' => int, 'msg' => string, 'selector' => int, 'token' => int, 'expires' => timestamp]
     *
     * On error return array:
     * ['err' => int, 'uid' => bool|int, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Too many attempts
     * 3 - User already exists
     * 4 - Unexpected error, it is unable to store user in database
     * 5 - User was forbidden
     * 6 - Unexpected error, can't create activation token
     * 7 - Unexpected error, it is unable to store user in social database
     *
     * @param string $email
     * @param string $pswd
     * @param int $roleId
     * @param bool $provider
     * @param bool $providerKey
     * @return array
     */
    public function userSet($email, $pswd, $roleId, $provider = false, $providerKey = false)
    {
        // Default return data
        $data = [
            'uid' => false,
            'err' => 1,
            'msg' => 'Unexpected error.',
        ];

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts('signup', 10)) {

            // Err: Too many attempts
            $data['err'] = 2;
            $data['msg'] = 'Too many signup attempts.';
            return $data;
        }

        // Get database connection
        $db = $this->connection->connect();

        // Check if activated or forbidden user already exists
        $q = $db->prepare('SELECT id, status FROM auth_users WHERE email = ? AND status = 1 OR status = 3');
        $q->execute([$email]);
        $r = $q->fetch();

        if ($r) {
            // Email address was forbidden
            if ($r['status'] == 3) {
                $data['err'] = 5;
                $data['msg'] = 'User was forbidden.';
                return $data;
            }

            // User already exists
            $data['err'] = 3;
            $data['msg'] = 'User already exists.';
            return $data;
        }

        // Do following only when activation is required
        if ($this->config['withActivation']) {

            // Check if there is some user with same email and pending activation
            $q = $db->prepare('
                SELECT au.id 
                FROM auth_users au
                LEFT JOIN auth_tokens_activation ata
                ON ata.user_id = au.id                
                WHERE au.email = ?
                AND au.status < 2
                AND ata.expires > UNIX_TIMESTAMP()
            ');
            $q->execute([$email]);
            $r = $q->fetch();

            // We found user with same email and pending activation
            // Err: User already exists
            if ($r) {
                $data['err'] = 3;
                $data['msg'] = 'User already exists.';
                return $data;
            }

            // We are here, it means that we may have some expired inactivated
            // users with status 0, so update theirs status to expired
            $q = $db->prepare('UPDATE auth_users SET status = 2 WHERE email = ? AND status = 0');
            $q->execute([$email]);
        }

        // Determine user status based on activation
        $status = $this->config['withActivation'] ? 0 : 1;

        // Store user in database
        $q = $db->prepare('INSERT INTO auth_users (role_id, email, pswd, signup_ts, status) VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)');
        $q->execute([$roleId, $email, hash_hmac('sha256', $pswd, $this->config['salt']), $status]);

        // Try to get user id
        $uid = $q->rowCount() > 0 ? $db->lastInsertId() : false;

        // Err: Unexpected error, we don't have user id
        if (!$uid) {
            $data['err'] = 4;
            $data['msg'] = 'Unexpected error, it is unable to store user in database.';
            return $data;
        }

        // Update user id value
        $data['uid'] = $uid;

        // Generate activation token when activation is required
        if ($this->config['withActivation']) {

            $res = $this->generateToken($uid, 'auth_tokens_activation', time() + $this->config['confirmationTime']);

            if ($res['err']) {
                $data['err'] = 6;
                $data['msg'] = $res['msg'];
            }

            $data['token'] = $res['token'];
            $data['selector'] = $res['selector'];
            $data['expires'] = $res['expires'];
        }

        // If it's social sign-up, add user also to table auth_users_social
        if ($provider && $providerKey) {
            $q = $db->prepare('INSERT INTO auth_users_social (provider, provider_key, user_id) VALUES (?, ?, ?)');
            $q->execute([$provider, $providerKey, $uid]);

            if ($q->rowCount() < 1) {
                $data['err'] = 7;
                $data['msg'] = 'Unexpected error, it is unable to store user in social database.';
                return $data;
            }
        }

        $data['err'] = false;
        $data['msg'] = 'User was successfully signed up.';

        return $data;
    }

    /**
     * Get user from database
     *
     * On success return array:
     * ['err' => false, 'uid' => int, 'msg' => string]
     *
     * On error return array:
     * ['err' => int, 'uid' => false|int, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Too many attempts
     * 3 - Unexpected error, more users exist
     * 4 - User does not exist
     * 5 - User was forbidden
     * 6 - User is suspended
     * 7 - Password is not set
     * 8 - Password isn't correct
     * 9 - Inactive, pending activation
     * 10 - Inactive, can be re-activated
     * 11 - User does not exist in auth_users_social
     *
     * @param string $email
     * @param string $pswd
     * @param bool $social
     * @param bool|string $provider
     * @return array
     */
    public function userGet($email, $pswd, $social = false, $provider = false)
    {
        // Default return data
        $data = [
            'uid' => false,
            'err' => 1,
            'msg' => 'Unexpected error.',
        ];

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts('login', 10)) {

            // Err: Too many attempts
            $data['err'] = 2;
            $data['msg'] = 'Too many login attempts.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Get unexpired or forbidden user from database
        $q = $db->prepare('SELECT id, pswd, status FROM auth_users WHERE email = ? AND status < 2 OR status = 3');
        $q->execute([$email]);
        $r = $q->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount = count($r);

        // Err: Unexpected error, more users exist
        if ($rowCount > 1) {
            $data['err'] = 3;
            $data['msg'] = 'Unexpected error, more users exist.';
            return $data;
        }

        // Err: User does not exist
        if ($rowCount == 0) {
            $data['err'] = 4;
            $data['msg'] = 'User does not exist.';
            return $data;
        }

        // Update user id value
        $data['uid'] = $r['id'];

        // Err: User was forbidden
        if ($r['status'] == 3) {
            $data['err'] = 5;
            $data['msg'] = 'User was forbidden.';
            return $data;
        }

        // Is user suspended?
        $q = $db->prepare('
            SELECT till_ts 
            FROM auth_users_suspended 
            WHERE user_id = ? 
            AND till_ts > UNIX_TIMESTAMP()
            ORDER BY till_ts DESC
            LIMIT 1
        ');
        $q->execute([$r['id']]);
        $row = $q->fetch();

        // Err: User is suspended
        if ($row) {
            $data['err'] = 6;
            $data['ts'] = $row['till_ts'];
            $data['msg'] = 'User is suspended.';
        }

        // Check password, but only if it's not social login
        if (!$social && !$this->token->compare(hash_hmac('sha256', $pswd, $this->config['salt']), $r['pswd'])) {

            // Password does not match

            // Does user exists in auth_users_social
            $q = $db->prepare('SELECT COUNT(*) FROM auth_users_social WHERE user_id = ?');
            $q->execute([$r['id']]);

            // User exists in auth_users_social
            // Err: Password is not set
            if ($q->fetchColumn() > 0) {
                $data['err'] = 7;
                $data['msg'] = 'Password is not set.';
                return $data;
            }

            // Err: Password isn't correct
            $data['err'] = 8;
            $data['msg'] = 'Password isn\'t correct.';
            return $data;
        }

        // Is activation required and user is not activated?
        if ($this->config['withActivation'] && $r['status'] == 0) {

            // Check if user has some pending activation
            $q = $db->prepare('
                SELECT COUNT(*)
                FROM auth_tokens_activation 
                WHERE user_id = ? AND expires > UNIX_TIMESTAMP()
            ');
            $q->execute([$r['id']]);

            // User has some pending activations
            // Err: Pending activation
            if ($q->fetchColumn() > 0) {
                $data['err'] = 9;
                $data['msg'] = 'User isn\'t activated, but has pending activation.';
                return $data;
            }

            $data['err'] = 10;
            $data['msg'] = 'User isn\'t activated, but can be re-activated.';
            return $data;
        }

        // Check if user is stored in auth_users_social, but only if it is social login and provider is provided
        if ($social && $provider) {

            // Does user exists in auth_users_social
            $q = $db->prepare('SELECT COUNT(*) FROM auth_users_social WHERE user_id = ? AND provider = ?');
            $q->execute([$r['id'], $provider]);

            // User never logged in with current provider
            // Err: User does not exist in auth_users_social
            if ($q->fetchColumn() == 0) {
                $data['err'] = 11;
                $data['msg'] = 'User does not exist in auth_users_social.';
                return $data;
            }
        }

        $data['err'] = false;
        $data['msg'] = 'User is correctly signed up.';

        return $data;
    }

    /**
     * Generate and store token in to table auth_tokens_permanent
     *
     * On success return array:
     * ['err' => false, 'selector' => string, 'token' => string, 'expires' => timestamp, 'msg' => string, 'new' => bool]
     *
     * On error return array:
     * ['err' => int, 'selector' => false, 'token' => false, 'expires' => false, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Unexpected error, it isn't possible to store token in to database
     * 3 - Too many activation attempts
     * 4 - Id does not exist
     * 5 - User is expired
     * 6 - User is forbidden
     * 7 - User is suspended
     *
     * @param $uid
     * @return array
     */
    public function userActivationGenerateToken($uid)
    {
        // Default return data
        $data = [
            'err' => 1,
            'uid' => false,
            'msg' => 'Unexpected error.',
        ];

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts('activation-request', 5)) {

            // Err: Too many attempts
            $data['err'] = 3;
            $data['msg'] = 'Too many activation attempts.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Check if email is in DB
        $q = $db->prepare('SELECT status FROM auth_users WHERE id = ?');
        $q->execute([$uid]);
        $r = $q->fetch();

        // Err: Email does not exist
        if (!$r) {
            $data['err'] = 4;
            $data['msg'] = 'Id does not exist.';
            return $data;
        }

        // Update user id value
        $data['uid'] = $r['id'];

        // Err: User is expired.
        if ($r['status'] == 2) {
            $data['err'] = 5;
            $data['msg'] = 'User is expired.';
            return $data;
        }

        // Err: User is forbidden.
        if ($r['status'] == 3) {
            $data['err'] = 6;
            $data['msg'] = 'User is forbidden.';
            return $data;
        }

        // Is user suspended?
        $q = $db->prepare('
            SELECT till_ts 
            FROM auth_users_suspended 
            WHERE user_id = ? 
            AND till_ts > UNIX_TIMESTAMP()
            ORDER BY till_ts DESC
            LIMIT 1
        ');
        $q->execute([$uid]);
        $r = $q->fetch();

        // Err: User is suspended
        if ($r) {
            $data['err'] = 7;
            $data['ts'] = $r['till_ts'];
            $data['msg'] = 'User is suspended.';
        }

        // At first try to get existing token from DB instead of generating new token.
        // Generating tokens is expensive.
        $data = $this->getTokenByUid($data['uid'], 'auth_tokens_activation');
        if (!$data['err']) {
            $data['new'] = false;
            return $data;
        }

        // Generate activation token
        $data = $this->generateToken($uid, 'auth_tokens_activation', time() + $this->config['confirmationTime']);
        $data['new'] = true;

        return $data;
    }

    /**
     * Activate the user with activation token
     *
     * On success return array:
     * ['err' => false, 'uid' => int, 'msg' => string]
     *
     * On error return array:
     * ['err' => int, 'uid' => bool|int, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Too many attempts
     * 3 - Selector does not exist or is expired
     * 4 - Tokens mismatch
     * 5 - Unable to update user status
     *
     * @param string $selector
     * @param string $token
     * @return array
     */
    public function userActivationValidateToken($selector, $token)
    {
        // Check token
        $data = $this->validateToken($selector, $token, 'auth_tokens_activation', 'activation-confirmation');

        // Err: Invalid token
        if ($data['err']) return $data;

        // Get connection
        $db = $this->connection->connect();

        // Insert user activation
        $q = $db->prepare('UPDATE auth_users SET status = 1 WHERE id = ?');
        $q->execute([$data['uid']]);

        // Err: Unable to update user status
        if ($q->rowCount() < 1) {
            $data['err'] = 5;
            $data['msg'] = 'Unable to update user status.';
            return $data;
        }

        // Delete all activation tokens of current user
        $this->deleteTokensByUid($data['uid'], 'auth_tokens_activation');

        // Delete also other expired tokens with 5% chance
        $this->deleteExpiredTokens('auth_tokens_activation');

        // Successful response
        $data['err'] = false;
        $data['msg'] = 'User was successfully activated.';
        return $data;
    }

    /**
     * Generate and store token in to table auth_tokens_password
     *
     * On success return array:
     * ['err' => false, 'selector' => string, 'token' => string, 'expires' => timestamp, 'msg' => string, 'new' => bool]
     *
     * On error return array:
     * ['err' => int, 'selector' => false, 'token' => false, 'expires' => false, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Unexpected error, it isn't possible to store token in to database
     * 3 - Too many password attempts
     * 4 - Email does not exist
     * 5 - User is expired
     * 6 - User is forbidden
     * 7 - User is suspended
     *
     * @param $email
     * @return array
     */
    public function userChangePswdGenerateToken($email)
    {
        // Default return data
        $data = [
            'err' => 1,
            'uid' => false,
            'msg' => 'Unexpected error.',
        ];

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts('password-request', 5)) {

            // Err: Too many attempts
            $data['err'] = 3;
            $data['msg'] = 'Too many password attempts.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Check if email is in DB
        $q = $db->prepare('SELECT id, status FROM auth_users WHERE email = ?');
        $q->execute([$email]);
        $r = $q->fetch();

        // Err: Email does not exist
        if (!$r) {
            $data['err'] = 4;
            $data['msg'] = 'Email does not exist.';
            return $data;
        }

        // Update user id value
        $data['uid'] = $r['id'];

        // Err: User is expired.
        if ($r['status'] == 2) {
            $data['err'] = 5;
            $data['msg'] = 'User is expired.';
            return $data;
        }

        // Err: User is forbidden.
        if ($r['status'] == 3) {
            $data['err'] = 6;
            $data['msg'] = 'User is forbidden.';
            return $data;
        }

        // Is user suspended?
        $q = $db->prepare('
            SELECT till_ts 
            FROM auth_users_suspended 
            WHERE user_id = ? 
            AND till_ts > UNIX_TIMESTAMP()
            ORDER BY till_ts DESC
            LIMIT 1
        ');
        $q->execute([$data['uid']]);
        $r = $q->fetch();

        // Err: User is suspended
        if ($r) {
            $data['err'] = 7;
            $data['ts'] = $r['till_ts'];
            $data['msg'] = 'User is suspended.';
        }

        // At first try to get existing token from DB instead of generating new token.
        // Generating tokens is expensive.
        $data = $this->getTokenByUid($data['uid'], 'auth_tokens_password');
        if (!$data['err']) {
            $data['new'] = false;
            return $data;
        }

        // Generate password token
        $data = $this->generateToken($data['uid'], 'auth_tokens_password', time() + $this->config['confirmationTime']);
        $data['new'] = true;

        return $data;
    }

    /**
     * Change the user password
     *
     * On success return array:
     * ['err' => false, 'uid' => int, 'msg' => string]
     *
     * On error return array:
     * ['err' => int, 'uid' => bool|int, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Too many attempts
     * 3 - Selector does not exist or is expired
     * 4 - Tokens mismatch
     * 5 - Unable to update user password
     *
     * @param string $selector
     * @param string $token
     * @param string $pswd
     * @return array
     */
    public function userChangePswdValidateToken($selector, $token, $pswd)
    {
        // Check token
        $data = $this->validateToken($selector, $token, 'auth_tokens_password', 'password-confirmation');

        // Err: Invalid token
        if ($data['err']) return $data;

        // Get connection
        $db = $this->connection->connect();

        // Update user password
        $q = $db->prepare('UPDATE auth_users SET pswd = ? WHERE id = ?');
        $q->execute([hash_hmac('sha256', $pswd, $this->config['salt']), $data['uid']]);

        // Unable to update user password
        if ($q->rowCount() < 1) {
            $data['err'] = 5;
            $data['msg'] = 'Unable to update user password.';
            return $data;
        }

        // Delete all password tokens of current user
        $this->deleteTokensByUid($data['uid'], 'auth_tokens_password');

        // Successful response
        $data['msg'] = 'User password was successfully changed.';
        $data['err'] = false;
        return $data;
    }

    /**
     * Generate token and selector for given user and table
     *
     * On success return array:
     * ['err' => false, 'selector' => string, 'token' => string, 'expires' => timestamp, 'msg' => string]
     *
     * On error return array:
     * ['err' => int, 'selector' => false, 'token' => false, 'expires' => false, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Unexpected error, it isn't possible to store token in to database
     *
     * @param int $uid
     * @param string $tableName
     * @return array
     */
    private function generateToken($uid, $tableName, $expirationTs)
    {
        $data = [
            'err' => 1,
            'token' => false,
            'selector' => false,
            'expires' => false,
            'msg' => 'Unexpected error.',
        ];

        // Generate token and selector
        $selector = $this->token->generate(6);
        $token = $this->token->generate();

        // Get connection
        $db = $this->connection->connect();

        // Store token and selector in to database
        $q = $db->prepare('INSERT INTO ' . $tableName . ' (user_id, selector, token, expires) VALUES (?, ?, ?, ?)');
        $q->execute([$uid, $selector, hash('sha256', $token), $expirationTs]);

        // Err: Unable to store
        if ($q->rowCount() < 1) {
            $data['err'] = 2;
            $data['msg'] = 'Unexpected error, it isn\'t possible to store token in to database.';
            return $data;
        }

        // Return successful response
        $data['selector'] = $selector;
        $data['token'] = $token;
        $data['expires'] = $expirationTs;
        $data['msg'] = 'Token was successfully generated.';
        $data['err'] = false;
        return $data;
    }

    /**
     * Get token and selector for given user and table
     *
     * On success return array:
     * ['err' => false, 'token' => string, 'selector' => string, 'expires' => timestamp, 'msg' => string]
     *
     * On error return array:
     * ['err' => int, 'token' => false, 'selector' => false, 'expires' => false, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Token does not exist
     *
     * @param int $uid
     * @param string $tableName
     * @return array
     */
    private function getTokenByUid($uid, $tableName)
    {
        // Default return data
        $data = [
            'err' => 1,
            'token' => false,
            'selector' => false,
            'expires' => false,
            'msg' => 'Unexpected error.',
        ];

        // Get connection
        $db = $this->connection->connect();

        // Get data from database
        $q = $db->prepare('SELECT selector, token, expires FROM ' . $tableName . ' WHERE user_id = ? AND expires > UNIX_TIMESTAMP() ORDER BY expires DESC LIMIT 1');
        $q->execute([$uid]);
        $r = $q->fetch();

        // Err: Token does not exist
        if (!$r) {
            $data['err'] = 2;
            $data['msg'] = 'Token does not exist.';
            return $data;
        }

        // Return successful response
        $data['token'] = $r['token'];
        $data['selector'] = $r['selector'];
        $data['expires'] = $r['expires'];
        $data['err'] = false;
        $data['msg'] = 'Valid token was found.';
        return $data;
    }

    /**
     * At first check if user has enough attempts to perform validation.
     * Only then check if selector and token are valid.
     *
     * On success return array:
     * ['err' => false, 'uid' => int, 'msg' => string]
     *
     * On error return array:
     * ['err' => int, 'uid' => false, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Too many attempts
     * 3 - Selector does not exist or is expired
     * 4 - Tokens mismatch
     *
     * @param string $selector
     * @param string $token
     * @param string $tableName
     * @param string $attemptName
     * @return array
     */
    private function validateToken($selector, $token, $tableName, $attemptName)
    {
        // Default return data
        $data = [
            'err' => 1,
            'uid' => false,
            'msg' => 'Unexpected error.',
        ];

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts($attemptName)) {

            // Err: Too many attempts
            $data['err'] = 2;
            $data['msg'] = 'Too many ' . $attemptName . ' attempts.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Search token by valid selector
        $q = $db->prepare('
            SELECT user_id, token
            FROM ' . $tableName . ' 
            WHERE selector = ? AND expires > UNIX_TIMESTAMP()            
        ');
        $q->execute([$selector]);
        $r = $q->fetch();

        // Selector does not exist or is expired
        if (!$r) {
            $data['err'] = 3;
            $data['msg'] = 'Selector does not exist or is expired.';
            return $data;
        }

        // Are tokens equal?
        $token = hash('sha256', $token);
        if (!$this->token->compare($token, $r['token'])) {
            $data['err'] = 4;
            $data['msg'] = 'Tokens mismatch.';
            return $data;
        }

        // Update user id value
        $data['uid'] = $r['user_id'];

        // Return successful response
        $data['err'] = false;
        $data['msg'] = 'Token is valid.';
        return $data;
    }

    /**
     * Delete all expired tokens from given table with given percent chance
     * @param $tableName
     * @param int $chanceDelete
     */
    private function deleteExpiredTokens($tableName, $chanceDelete = 5)
    {
        if (rand(1, 100) <= $chanceDelete) {

            // Get connection
            $db = $this->connection->connect();

            // Delete expired tokens
            $q = $db->prepare('DELETE FROM ' . $tableName . ' WHERE expires <= UNIX_TIMESTAMP()');
            $q->execute();
        }
    }

    /**
     * Delete all tokens from given table for given user id
     * @param $uid
     * @param $tableName
     */
    private function deleteTokensByUid($uid, $tableName)
    {
        // Get connection
        $db = $this->connection->connect();

        // Delete expired tokens
        $q = $db->prepare('DELETE FROM ' . $tableName . ' WHERE user_id = ?');
        $q->execute([$uid]);
    }

    /**
     * Return true if user does not exceeded allowed attempts count and store new attempt
     * Return false if user exceeded allowed attempts count
     * @param $attemptName
     * @param int $chanceDelete
     * @return bool
     */
    private function attempts($attemptName, $chanceDelete = 5)
    {
        // Get individual(action, ip, agent) attempts count for specified action and date interval
        $attemptsLimit = $this->config[$attemptName][0];
        $attemptsInterval = $this->config[$attemptName][1];
        $attemptsCount = $this->attempts->getAttemptsCount($attemptName, $attemptsInterval);

        // Does user exceeded allowed attempts count?
        // Err: Too many attempts
        if ($attemptsCount >= $attemptsLimit) {
            return false;
        }

        // Store attempt of current ip, agent
        $this->attempts->setAttempt($attemptName);

        // 5% chance to delete old attempts
        if (rand(1, 100) <= $chanceDelete) {
            $this->attempts->deleteAttempts($attemptName, $attemptsInterval);
        }

        return true;
    }

    /**
     * Store auth credentials for permanent login
     * Return true on success otherwise false
     * @param int $uid
     * @return bool
     */
    private function userLoginPermanent($uid)
    {
        // 5% chance to delete old expired permanent login tokens
        $this->deleteExpiredTokens('auth_tokens_permanent', 5);

        // Generate and store permanent login token
        $expirationTs = time() + $this->config['permanent'] * 60 * 60;
        $data = $this->generateToken($uid, 'auth_tokens_permanent', $expirationTs);

        // Err: Unable to generate or store permanent login token
        if ($data['err']) return false;

        // Create auth cookie
        $this->sessions->setCookie($this->config['cookieName'], $data['selector'] . ':' . $data['token'], $this->config['permanent'] . ' days');

        return true;
    }

    /**
     * Delete permanent login cookie and all tokens in table auth_permanent associated with current user id
     * It logs out the user on all devices.
     */
    private function userLogoutPermanent()
    {
        $uid = $this->sessions->getFromSession('logged');

        if (!$uid) {
            $uid = $this->isUserPermanentlyLogged();
        }

        if ($uid) {
            $this->sessions->delCookie($this->config['cookieName']);
            $this->deleteTokensByUid($uid, 'auth_tokens_permanent');
        }
    }

    /**
     * Return user id when user is permanently logged, otherwise false
     * @return bool|int
     */
    private function isUserPermanentlyLogged()
    {
        $permanentLoginCookieVal = $this->sessions->getCookie($this->config['cookieName']);

        // Does permanent login cookie exist?
        if (!$permanentLoginCookieVal) {
            return false;
        }

        // Try to get selector and token
        $cookie = explode(':', $permanentLoginCookieVal);
        if (!isset($cookie[0]) || !isset($cookie[1])) {

            // Err: Selector or token does not exist
            // Delete invalid login cookie
            $this->sessions->delCookie($this->config['cookieName']);
            return false;
        }

        // Validate login cookie against database
        $data = $this->validateToken($cookie[0], $cookie[1], 'auth_tokens_permanent', 'is-logged');

        // Err: Selector or token is: invalid, expired or different
        if ($data['err']) {

            // Delete invalid login cookie
            $this->sessions->delCookie($this->config['cookieName']);
            return false;
        }

        return $data['user_id'];
    }
}