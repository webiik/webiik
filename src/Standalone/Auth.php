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
    // Todo: Add auto logout feature to Auth class

    /**
     * @var Session
     */
    private $session;

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
        // How many hours can be the user logged in
        'permanent' => 0,
        // Require account activation or not
        'withActivation' => false,
        // Number of attempt to login, sign-up, token actions
        // Allow 60 attempts per 1 hour from same ip
        'attemptsLimit' => [60, 3600],
        // Automatically delete expired tokens and attempts
        'autoDelete' => true,
        // Allow validate token within 24 hours
        'confirmationTime' => 86400,
        // Permanent login cookie name
        'loginSessionName' => 'logged',
        // Permanent login cookie name
        'permanentCookieName' => 'PC',
        // Password salt
        'salt' => 'r489cjd3Xhed',
        // Distinguish languages
        'lang' => '',
    ];

    /**
     * @var array
     */
    private $allowedAuthTokenTableNames = [
        'auth_tokens_activation',
        'auth_tokens_re_activation',
        'auth_tokens_pairing_google',
        'auth_tokens_re_pairing_google',
        'auth_tokens_pairing_facebook',
        'auth_tokens_re_pairing_facebook',
        'auth_tokens_pairing_twitter',
        'auth_tokens_re_pairing_twitter',
        'auth_tokens_deletion',
        'auth_tokens_permanent',
        'auth_tokens_pswd_renewal',
    ];

    /**
     * Auth constructor.
     * @param Cookie $cookie
     * @param Session $session
     * @param Connection $connection
     * @param Token $token
     * @param Attempts $attempts
     */
    public function __construct(Cookie $cookie, Session $session, Connection $connection, Token $token, Attempts $attempts)
    {
        $this->cookie = $cookie;
        $this->session = $session;
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
     * Configure salt
     * @param string $string
     */
    public function confLang($string)
    {
        $this->config['lang'] = $string;
        $this->config['loginSessionName'] .= $string;
        $this->config['permanentCookieName'] .= $string;
    }

    /**
     * Configure login session name
     * @param string $string
     */
    public function confLoginSessionName($string)
    {
        $this->config['loginSessionName'] = $string . $this->config['lang'];
    }

    /**
     * Configure permanent cookie name
     * @param string $string
     */
    public function confCookieName($string)
    {
        $this->config['permanentCookieName'] = $string . $this->config['lang'];
    }

    /**
     * Configure how many days can permanently logged user be logged in
     * @param int $hours
     */
    public function confPermanent($hours)
    {
        $this->config['permanent'] = $hours;
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
     * Configure attempts limit per time period from same IP
     * @param int $count
     * @param int $sec
     */
    public function confAttemptsLimit($count, $sec)
    {
        $this->config['attemptsLimit'] = [$count, $sec];
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
     * @param bool $permanent
     */
    public function userLogin($uid, $permanent = false)
    {
        $this->session->sessionRegenerateId();
        $this->session->setToSession($this->config['loginSessionName'], $uid);

        if ($permanent && $this->config['permanent'] > 0) {
            $expirationTs = time() + $this->config['permanent'] * 60 * 60;
            $this->authCookieCreate($uid, $this->config['permanentCookieName'], $expirationTs, 'auth_tokens_permanent');
        }
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * Return user id on success otherwise false
     * @return bool|int
     */
    public function isUserLogged()
    {
        $uid = $this->session->getFromSession($this->config['loginSessionName']);

        if ($uid) {
            return $uid;
        }

        if ($this->config['permanent'] > 0) {
            $uid = $this->authCookieCheck($this->config['permanentCookieName'], 'auth_tokens_permanent');
        }

        if ($uid) {
            $this->userLogin($uid);
        }

        return $uid;
    }

    /**
     *  Delete all logged indicators and for sure destroy session
     */
    public function userLogout()
    {
        $this->authCookieDelete($this->config['permanentCookieName'], 'auth_tokens_permanent');
        $this->session->delFromSession($this->config['loginSessionName']);
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
     * ['err' => false, 'uid' => int, 'msg' => string]
     *
     * On success with activation will add:
     * 'tokens' =>
     *      ['activation' => ['selector' => int, 'token' => int, 'expires' => timestamp]
     *      ['re-activation' => ['selector' => int, 'token' => int, 'expires' => timestamp]
     *
     * On success with social will add:
     * ['provider' => string, 'providerKey' => mixed]
     *
     * On error return array:
     * ['err' => int, 'uid' => bool|int, 'msg' => string]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Too many attempts
     * 3 - User already exists
     * 4 - Unexpected error, it is unable to store user in database
     * 5 - Unexpected error, can't generate token
     * 6 - Unexpected error, it is unable to store user in social database
     * 7 - IP is banned
     *
     * @param string $email
     * @param string $pswd
     * @param int $roleId
     * @param bool|string $provider
     * @return array
     */
    public function userSet($email, $pswd, $roleId, $provider = false)
    {
        // Default return data
        $data = [
            'uid' => false,
            'err' => 1,
            'msg' => 'Unexpected error.',
        ];

        // Is IP banned?
        if ($banned = $this->userIsBanned()) {
            // Err: User is banned
            $data['err'] = 7;
            $data['ts'] = $banned['till_ts'];
            $data['msg'] = 'User is banned.';
        }

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 2;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // Get database connection
        $db = $this->connection->connect();

        // Check if activated user already exists
        $q = $db->prepare('SELECT id, status FROM auth_users WHERE email = ? AND status = 1');
        $q->execute([$email]);
        $r = $q->fetch();

        if ($r) {
            // User already exists
            $data['err'] = 3;
            $data['msg'] = 'User already exists.';
            return $data;
        }

        // Set default user status to active
        $status = 1;

        // Do following only when account activation is required
        if ($this->config['withActivation']) {

            // Set default user status to inactive
            $status = 0;

            // Check if there is some user with same email and pending activation
            $q = $db->prepare('
                SELECT au.id 
                FROM auth_users au
                LEFT JOIN auth_tokens_activation ata
                ON ata.user_id = au.id                
                WHERE au.email = ?
                AND au.status = 0
                AND ata.expires > UNIX_TIMESTAMP()
            ');
            $q->execute([$email]);
            $r = $q->fetch();

            // We found user with same email and pending activation
            // Err: User already exists
            if ($r) {

                // Get re-activation token
                // Every user with pending activation should have re-activation token too
                $token = $this->tokenUserHasValid($r['id'], 'auth_tokens_re_activation');

                // Err: Unable to get any re-activation token
                if ($token['err']) {
                    $data['err'] = 5;
                    $data['msg'] = $token['msg'];
                    return $data;
                }

                // Generate new re-activation token
                $token = $this->tokenGenerate($r['id'], 'auth_tokens_re_activation', $token['expires']);

                // Err: Unable to generate re-activation token
                if ($token['err']) {
                    $data['err'] = 5;
                    $data['msg'] = $token['msg'];
                    return $data;
                }

                // Err: User with pending activation exists
                $data['tokens']['re-activation']['token'] = $token['token'];
                $data['tokens']['re-activation']['selector'] = $token['selector'];
                $data['tokens']['re-activation']['expires'] = $token['expires'];
                $data['err'] = 3;
                $data['msg'] = 'User with pending activation exists.';
                return $data;
            }

            // We are here, it means that we may have some expired inactivated
            // users with status 0, so update theirs status to expired
            $q = $db->prepare('UPDATE auth_users SET status = 2 WHERE email = ? AND status = 0');
            $q->execute([$email]);
        }

        // Set empty password if user is signed up via social
        if ($provider) {
            $pswd = '';
        } else {
            $pswd = hash_hmac('sha256', $pswd, $this->config['salt']);
        }

        // Add user in to table auth_users
        $q = $db->prepare('INSERT INTO auth_users (role_id, email, pswd, signup_ts, status) VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)');
        $q->execute([$roleId, $email, $pswd, $status]);

        // Try to get user id
        $uid = $q->rowCount() > 0 ? $db->lastInsertId() : false;

        // Err: Unexpected error, we don't have user id
        if (!$uid) {
            $data['err'] = 4;
            $data['msg'] = 'Unexpected error, it is unable to store user in database.';
            return $data;
        }

        // Set response data user id value
        $data['uid'] = $uid;

        // If it's social sign-up, add user also to table auth_users_social
        if ($provider) {
            $q = $db->prepare('INSERT INTO auth_users_social (provider, user_id) VALUES (?, ?)');
            $q->execute([$provider, $uid]);

            if ($q->rowCount() < 1) {
                $data['err'] = 6;
                $data['msg'] = 'Unexpected error, it is unable to store user in social database.';
                return $data;
            }
        }

        // Generate activation and re-activation token when activation is required
        if ($this->config['withActivation']) {

            $tokenExpirationTs = time() + $this->config['confirmationTime'];

            $token = $this->tokenGenerate($uid, 'auth_tokens_re_activation', $tokenExpirationTs);

            // Err: Unable to generate token
            if ($token['err']) {
                $data['err'] = 5;
                $data['msg'] = $token['msg'];
                return $data;
            }

            $data['tokens']['re-activation']['token'] = $token['token'];
            $data['tokens']['re-activation']['selector'] = $token['selector'];
            $data['tokens']['re-activation']['expires'] = $token['expires'];

            $token = $this->tokenGenerate($uid, 'auth_tokens_activation', $tokenExpirationTs);

            // Err: Unable to generate token
            if ($token['err']) {
                $data['err'] = 5;
                $data['msg'] = $token['msg'];
                return $data;
            }

            $data['tokens']['activation']['token'] = $token['token'];
            $data['tokens']['activation']['selector'] = $token['selector'];
            $data['tokens']['activation']['expires'] = $token['expires'];
        }

        $data['err'] = false;
        $data['email'] = $email;
        $data['msg'] = 'User was successfully set.';

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
     * On error with activation err 4 will add:
     * ['tokens' => ['re-activation' => ['selector' => int, 'token' => int, 'expires' => timestamp]]
     *
     * On error with activation err 8 will add:
     * ['providers' => arr]
     *
     * On error with activation err 10 will add:
     * ['tokens' => ['re-pairing' => ['selector' => int, 'token' => int, 'expires' => timestamp]]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Too many attempts
     * 3 - Can't generate token
     * 4 - User is not activated
     * 5 - Unexpected error, more users exist
     * 6 - User does not exist
     * 7 - User is banned
     * 8 - Password is not set
     * 9 - Password does not match
     * 10 - Social account is not paired with main account
     *
     * @param string $email
     * @param string $pswd
     * @param bool|string $provider
     * @return array
     */
    public function userGet($email, $pswd, $provider = false)
    {
        // Default return data
        $data = [
            'uid' => false,
            'err' => 1,
            'msg' => 'Unexpected error.',
        ];

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 2;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // If it's not social log-in, password is required
        if (!$provider && (!$pswd || $pswd == '')) {
            $data['err'] = 9;
            $data['msg'] = 'Incorrect password.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Check if activated user exists
        $q = $db->prepare('SELECT id, pswd, status FROM auth_users WHERE email = ? AND status <= 1');
        $q->execute([$email]);
        $r = $q->fetchAll(\PDO::FETCH_ASSOC);
        $validUsersCount = count($r);

        if ($validUsersCount == 1) {

            // Set user id value
            $data['uid'] = $r[0]['id'];

            // Is user banned?
            if ($banned = $this->userIsBanned($r[0]['id'])) {
                // Err: User is banned
                $data['err'] = 7;
                $data['ts'] = $banned['till_ts'];
                $data['msg'] = 'User is banned.';
                return $data;
            }
        }

        if ($validUsersCount == 1 && $r[0]['status'] == 0) {

            if (!$this->config['withActivation']) {

                // We can't count user with status 0 as valid if activation is not required.
                $validUsersCount = 0;

            } else {

                // Inactive user or user with pending activation exists
                // Offer this user to get activation token again

                // Try to get existing valid re-activation token by user id
                $token = $this->tokenUserHasValid($r[0]['id'], 'auth_tokens_re_activation');

                // If there is existing valid re-activation token, use expiration date of this token
                if (!$token['err']) {
                    $expires = $token['expires'];
                } else {
                    $expires = time() + $this->config['confirmationTime'];
                }

                // Generate new re-activation token
                $token = $this->tokenGenerate($r[0]['id'], 'auth_tokens_re_activation', $expires);

                // Err during generating token
                if ($token['err']) {
                    $data['err'] = 3;
                    $data['msg'] = $token['msg'];
                    return $data;
                }

                // Err: User isn't activated, but can be (re)activated
                $data['tokens']['re-activation']['token'] = $token['token'];
                $data['tokens']['re-activation']['selector'] = $token['selector'];
                $data['tokens']['re-activation']['expires'] = $token['expires'];
            }
        }

        // Err: Unexpected error, more valid users exist
        if ($validUsersCount > 1) {
            $data['err'] = 5;
            $data['msg'] = 'Unexpected error, more users exist.';
            return $data;
        }

        // Err: User does not exist
        if ($validUsersCount == 0) {
            $data['err'] = 6;
            $data['msg'] = 'User does not exist.';
            return $data;
        }

        // Check password, but only if it's not social login
        if (!$provider && !$this->token->compare(hash_hmac('sha256', $pswd, $this->config['salt']), $r[0]['pswd'])) {

            // Password does not match

            // Does user exists in auth_users_social
            $q = $db->prepare('SELECT provider FROM auth_users_social WHERE user_id = ?');
            $q->execute([$r[0]['id']]);
            $providers = $q->fetchAll(\PDO::FETCH_ASSOC);

            // User exists in auth_users_social
            // Err: User previously logged via social and did not set password for 'classic' login
            if ($providers) {
                $data['err'] = 8;
                $data['msg'] = 'User previously logged in via social account and did not set password for \'classic\' login.';
                $data['providers'] = [];
                foreach ($providers as $key => $arr) {
                    $data['providers'][] = ucfirst($arr['provider']);
                }
                return $data;
            }

            // Err: Password isn't correct
            $data['err'] = 9;
            $data['msg'] = 'Password does not match.';
            return $data;
        }

        // Err: User isn't activated, but can be (re)activated
        if (isset($data['tokens'])) {
            $data['err'] = 4;
            $data['msg'] = 'User isn\'t activated, but can be (re)activated.';
            return $data;
        }

        // Check if user is stored in auth_users_social, but only if it is social login
        if ($provider) {

            // Does user exists in auth_users_social
            $q = $db->prepare('SELECT COUNT(*) FROM auth_users_social WHERE user_id = ? AND provider = ?');
            $q->execute([$r[0]['id'], $provider]);

            // User never logged in with current provider, but account with same email already exists
            // You can offer to pair current provider with existing account
            // Err: User already exists in auth_users but does not exist in auth_users_social
            if ($q->fetchColumn() == 0) {

                // Try to get existing valid re-pairing token by user id
                $token = $this->tokenUserHasValid($r[0]['id'], 'auth_tokens_re_pairing_' . $provider);

                // If there is existing valid re-pairing token, use expiration date of this token
                if (!$token['err']) {
                    $expires = $token['expires'];
                } else {
                    $expires = time() + $this->config['confirmationTime'];
                }

                // Generate new re-pairing token
                $token = $this->tokenGenerate($r[0]['id'], 'auth_tokens_re_pairing_' . $provider, $expires);

                // Problem during generating token
                if ($token['err']) {
                    $data['err'] = 3;
                    $data['msg'] = $token['msg'];
                }

                // Err: User already exists in auth_users but does not exist in auth_users_social
                $data['tokens']['re-pairing']['provider'] = $provider;
                $data['tokens']['re-pairing']['token'] = $token['token'];
                $data['tokens']['re-pairing']['selector'] = $token['selector'];
                $data['tokens']['re-pairing']['expires'] = $token['expires'];
                $data['err'] = 10;
                $data['msg'] = 'Social account is not paired with main account.';
                return $data;
            }
        }

        $data['err'] = false;
        $data['email'] = $email;
        $data['msg'] = 'User has been successfully signed in.';

        return $data;
    }

    /**
     * Create password renewal token
     *
     * On success return array:
     * ['err' => false, 'msg' => string, 'uid' => int, 'tokens' => arr]
     *
     * On error return array:
     * ['err' => int, 'msg' => string]
     *
     * Error codes:
     * 1 - Too many attempts
     * 2 - IP is banned
     * 3 - Email does not exist
     * 4 - Can't generate token
     *
     * @param string $email
     * @return array
     */
    public function userPswdUpdateStepOne($email)
    {
        // Is IP banned?
        if ($banned = $this->userIsBanned()) {
            // Err: User is banned
            $data['err'] = 2;
            $data['ts'] = $banned['till_ts'];
            $data['msg'] = 'User\'s IP is banned.';
            return $data;
        }

        // Check if user does not exceeded allowed attempts count and if not, store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 1;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Set user id to false as indicator that we don't user's identity now
        $uid = false;

        // Check if there is some activated user with given email
        $q = $db->prepare('SELECT id FROM auth_users WHERE email = ? AND status = 1');
        $q->execute([$email]);
        $r = $q->fetch();
        if ($r) {
            // We have found valid user associated with provided email
            $uid = $r['id'];
        }

        // If we didn't find activated user, but activation is required,
        // there is still chance that there can be user with pending activation.
        if (!$uid && $this->config['withActivation']) {

            // Accounts require activation...

            // Check if there is some user with same email and pending activation
            $q = $db->prepare('
                SELECT au.id 
                FROM auth_users au
                LEFT JOIN auth_tokens_activation ata
                ON ata.user_id = au.id                
                WHERE au.email = ?
                AND au.status = 0
                AND ata.expires > UNIX_TIMESTAMP()
            ');
            $q->execute([$email]);
            $r = $q->fetch();
            if ($r) {
                // We have found valid user associated with provided email
                $uid = $r['id'];
            }

        }

        if (!$uid) {
            // Err: There is no valid user for provided email
            $data['err'] = 3;
            $data['msg'] = 'Email does not exist.';
            return $data;
        }

        // Try to get existing valid re-activation token by user id
        $token = $this->tokenUserHasValid($r['id'], 'auth_tokens_pswd_renewal');

        // If there is existing valid re-activation token, use expiration date of this token
        if (!$token['err']) {
            $expires = $token['expires'];
        } else {
            $expires = time() + $this->config['confirmationTime'];
        }

        // Create password change request
        $token = $this->tokenGenerate($r['id'], 'auth_tokens_pswd_renewal', $expires);

        // Problem during generating token
        if ($token['err']) {
            $data['err'] = 4;
            $data['msg'] = $token['msg'];
        }

        // Renewal token
        $data['tokens']['pswd-renewal']['token'] = $token['token'];
        $data['tokens']['pswd-renewal']['selector'] = $token['selector'];
        $data['tokens']['pswd-renewal']['expires'] = $token['expires'];

        // Successful response
        $data['email'] = $email;
        $data['uid'] = $r['id'];
        $data['msg'] = 'Password renewal token has been created.';
        $data['err'] = false;
        return $data;
    }

    /**
     * Update the user password
     *
     * On success return array:
     * ['err' => false, 'msg' => string, 'uid' => int]
     *
     * On error return array:
     * ['err' => int, 'msg' => string]
     *
     * On error with activation err 4 will add:
     * ['ts' => timestamp]
     *
     * Error codes:
     * 1 - Too many attempts
     * 2 - Invalid token
     * 3 - Unable to update user password
     * 4 - IP is banned
     *
     * @param string $selector
     * @param string $token
     * @param string $pswd
     * @return array
     */
    public function userPswdUpdateStepTwo($selector, $token, $pswd)
    {
        // Is IP banned?
        if ($banned = $this->userIsBanned()) {
            // Err: User is banned
            $data['err'] = 4;
            $data['ts'] = $banned['till_ts'];
            $data['msg'] = 'User\'s IP is banned.';
            return $data;
        }

        // Check if user does not exceeded allowed attempts count and if not, store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 1;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // Check token
        $validatedToken = $this->tokenValidate($selector, $token, 'auth_tokens_pswd_renewal');

        // Err: Invalid token
        if ($validatedToken['err']) {
            $data['err'] = 2;
            $data['msg'] = $validatedToken['msg'];
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Update user password
        $q = $db->prepare('UPDATE auth_users SET pswd = ? WHERE id = ?');
        $q->execute([hash_hmac('sha256', $pswd, $this->config['salt']), $validatedToken['uid']]);

        // Unable to update user password
        if ($q->rowCount() < 1) {
            $data['err'] = 3;
            $data['msg'] = 'Unable to update user password.';
            return $data;
        }

        // Delete password renewal token
        $this->tokenDeleteBySelector($validatedToken['selector'], 'auth_tokens_pswd_renewal');

        // Successful response
        $date['uid'] = $validatedToken['uid'];
        $data['msg'] = 'User password has been changed.';
        $data['err'] = false;
        return $data;
    }

    /**
     * Activate the user with activation token
     *
     * On success return array:
     * ['err' => false, 'msg' => string, 'uid' => int]
     *
     * On error return array:
     * ['err' => int, 'msg' => string]
     *
     * On error with activation err 5 will add:
     * ['ts' => timestamp]
     *
     * Error codes:
     * 1 - Too many attempts
     * 2 - Invalid token
     * 3 - Account has been already activated
     * 4 - Another user has already activated the account
     * 5 - Unable to activate user
     * 6 - IP is banned
     *
     * @param string $selector
     * @param string $token
     * @return array
     */
    public function userActivate($selector, $token)
    {
        // Is IP banned?
        if ($banned = $this->userIsBanned()) {
            // Err: User is banned
            $data['err'] = 6;
            $data['ts'] = $banned['till_ts'];
            $data['msg'] = 'User\'s IP is banned.';
            return $data;
        }

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 1;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // Check token
        $validatedToken = $this->tokenValidate($selector, $token, 'auth_tokens_activation');

        // Err: Invalid token
        if ($validatedToken['err']) {
            $data['err'] = 2;
            $data['msg'] = $validatedToken['msg'];
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Check if there is no other activated user
        $q = $db->prepare('
            SELECT id, email
            FROM auth_users 
            WHERE status = 1 
            AND email = (SELECT email FROM auth_users WHERE id = ?)
            LIMIT 1
        ');
        $q->execute([$validatedToken['uid']]);
        $r = $q->fetch(\PDO::FETCH_ASSOC);

        if ($r) {
            if ($validatedToken['uid'] == $r['id']) {
                // Err: User has already activated the account
                $data['msg'] = 'Account has been already activated.';
                $data['err'] = 3;
            } else {
                // Err: Another user has already activated the account
                $data['msg'] = 'Another user has already activated the account.';
                $data['err'] = 4;
            }
            return $data;
        }

        // Update user status
        $q = $db->prepare('UPDATE auth_users SET status = 1 WHERE id = ?');
        $q->execute([$validatedToken['uid']]);

        // Err: Unable to update user status
        if ($q->rowCount() < 1) {
            $data['err'] = 5;
            $data['msg'] = 'Unable to update user status.';
            return $data;
        }

        // Expire other inactive accounts with same email address
        $q = $db->prepare('UPDATE auth_users SET status = 2 WHERE id != ? AND email = ?');
        $q->execute([$validatedToken['uid'], $r['email']]);

        // Successful response
        $date['uid'] = $validatedToken['uid'];
        $data['err'] = false;
        $data['msg'] = 'User was successfully activated.';
        return $data;
    }

    /**
     * Pair social account with user's account
     *
     * On success return array:
     * ['err' => false, 'msg' => string, 'uid' => int]
     *
     * On error return array:
     * ['err' => int, 'msg' => string]
     *
     * On error with activation err 4 will add:
     * ['ts' => timestamp]
     *
     * Error codes:
     * 1 - Too many attempts
     * 2 - Invalid provider
     * 3 - Invalid token
     * 4 - Unable to store user in auth_users_social
     * 5 - IP is banned
     *
     * @param string $selector
     * @param string $token
     * @param string $provider
     * @param string $tableName
     * @return array
     */
    public function userPairSocialAccount($selector, $token, $provider, $tableName)
    {
        // Is IP banned?
        if ($banned = $this->userIsBanned()) {
            // Err: User is banned
            $data['err'] = 5;
            $data['ts'] = $banned['till_ts'];
            $data['msg'] = 'User\'s IP is banned.';
            return $data;
        }

        // Check if user does not exceeded allowed attempts count and if not, store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 1;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // Check token
        $validatedToken = $this->tokenValidate($selector, $token, $tableName);

        // Err: Invalid token
        if ($validatedToken['err']) {
            if ($validatedToken['err'] == 2) {
                $data['err'] = 2;
            } else {
                $data['err'] = 3;
            }
            $data['msg'] = $validatedToken['msg'];
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Insert user
        $q = $db->prepare('INSERT INTO auth_users_social (provider, user_id) VALUES (?, ?)');
        $q->execute([$provider, $validatedToken['uid']]);

        // Err: Unable to store user in auth_users_social
        if ($q->rowCount() < 1) {
            $data['err'] = 3;
            $data['msg'] = 'Unable to store user in auth_users_social.';
            return $data;
        }

        // Delete password renewal token
        $this->tokenDeleteBySelector($validatedToken['selector'], $tableName);

        // Successful response
        $date['uid'] = $validatedToken['uid'];
        $data['msg'] = 'Social account has been paired with user account.';
        $data['err'] = false;
        return $data;
    }

    /**
     * Update the user status to 3 (marked for deletion)
     *
     * On success return array:
     * ['err' => false, 'msg' => string, 'uid' => int]
     *
     * On error return array:
     * ['err' => int, 'msg' => string]
     *
     * Error codes:
     * 1 - Too many attempts
     * 2 - Invalid token
     * 3 - Unable to update user status
     *
     * @param string $selector
     * @param string $token
     * @return array
     */
    public function userDelete($selector, $token)
    {
        // Check if user does not exceeded allowed attempts count and if not, store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 1;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // Check token
        $validatedToken = $this->tokenValidate($selector, $token, 'auth_tokens_deletion');

        // Err: Invalid token
        if ($validatedToken['err']) {
            $data['err'] = 2;
            $data['msg'] = $validatedToken['msg'];
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Update user status to: User requested deletion
        $q = $db->prepare('UPDATE auth_users SET status = 3 WHERE id = ?');
        $q->execute([$validatedToken['uid']]);

        // Unable to update user status
        if ($q->rowCount() < 1) {
            $data['err'] = 3;
            $data['msg'] = 'Unable to update user status.';
            return $data;
        }

        // Delete old this token
        $this->tokenDeleteBySelector($validatedToken['selector'], 'auth_tokens_deletion');

        // Successful response
        $date['uid'] = $validatedToken['uid'];
        $data['msg'] = 'User was marked for deletion.';
        $data['err'] = false;
        return $data;
    }

    /**
     * Return true if user does not exceeded allowed attempts count and store new attempt
     * Return false if user exceeded allowed attempts count
     * @param int $chanceDelete
     * @return bool
     */
    public function attempts($chanceDelete = 1)
    {
        // Get individual(action, ip, agent) attempts count for specified action and date interval
        $attemptsLimit = $this->config['attemptsLimit'][0];
        $attemptsInterval = $this->config['attemptsLimit'][1];
        $attemptsCount = $this->attempts->getAttemptsCount('auth', $attemptsInterval);

        // Does user exceeded allowed attempts count?
        // Err: Too many attempts
        if ($attemptsCount >= $attemptsLimit) {
            return false;
        }

        // Store attempt of current ip, agent
        $this->attempts->setAttempt('auth');

        // 1% chance to delete old attempts
        if ($this->config['autoDelete'] && rand(1, 100) <= $chanceDelete) {
            $this->attempts->deleteAttempts('auth', $attemptsInterval);
        }

        return true;
    }

    /**
     * Create auth cookie and store associated auth token in database
     * Return true on success otherwise false
     * @param int $uid
     * @param string $cookieName
     * @param string $expirationTs
     * @param string $tableName
     * @return bool
     */
    public function authCookieCreate($uid, $cookieName, $expirationTs, $tableName)
    {
        $data = $this->tokenGenerate($uid, $tableName, $expirationTs);

        // Err: Unable to generate token
        if ($data['err']) return false;

        // Create auth cookie
        $this->cookie->setCookie($cookieName, $data['selector'] . ':' . $data['token'], $expirationTs);

        return true;
    }

    /**
     * If cookie is valid return user id, otherwise try to delete cookie and return false
     * @param string $cookieName
     * @param string $tableName
     * @return bool|int
     */
    public function authCookieCheck($cookieName, $tableName)
    {
        $cookieVal = $this->cookie->getCookie($cookieName);

        // Does auth cookie exist?
        if (!$cookieVal) {
            return false;
        }

        // Try to get selector and token
        $cookie = explode(':', $cookieVal);
        if (!isset($cookie[0]) || !isset($cookie[1])) {

            // Err: Selector or token does not exist
            // Delete invalid auth cookie
            $this->cookie->delCookie($cookieName);
            return false;
        }

        // Validate auth cookie against database
        $data = $this->tokenValidate($cookie[0], $cookie[1], $tableName);

        // Err: Selector or token is: invalid, expired or different
        if ($data['err']) {

            // Delete invalid auth cookie
            $this->cookie->delCookie($cookieName);
            return false;
        }

        return $data['user_id'];
    }

    /**
     * Delete permanent login cookie and all auth tokens associated with current user id
     * @param string $cookieName
     * @param string $tableName
     */
    public function authCookieDelete($cookieName, $tableName)
    {
        $uid = $this->session->getFromSession($this->config['loginSessionName']);

        if (!$uid) {
            $uid = $this->authCookieCheck($cookieName, $tableName);
        }

        if ($uid) {
            $this->cookie->delCookie($cookieName);
            $this->tokenDeleteById($uid, $tableName);
        }
    }

    /**
     * Add allowed auth_tokens_... table name
     * In case you need additional table for auth_tokens.
     * @param $tableName
     */
    public function tokenAddAllowedTableName($tableName)
    {
        $this->allowedAuthTokenTableNames[] = $tableName;
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
     * 2 - Table name is not allowed
     * 3 - Too many attempts
     * 4 - Unexpected error, it isn't possible to store token in to database
     *
     * @param int $uid
     * @param string $tableName
     * @param int $expirationTs
     * @param bool $cheap
     * @return array
     */
    public function tokenGenerate($uid, $tableName, $expirationTs, $cheap = false)
    {
        $data = [
            'err' => 1,
            'token' => false,
            'selector' => false,
            'expires' => false,
            'msg' => 'Unexpected error.',
        ];

        // Check table name
        if (!$this->tokenIsTableNameAllowed($tableName)) {
            // Err: Unapproved table name
            $data['err'] = 2;
            $data['msg'] = 'Table name is not allowed.';
            return $data;
        }

        // Check if user does not exceeded allowed attempts count and if not store new attempt
        if (!$this->attempts()) {
            // Err: Too many attempts
            $data['err'] = 3;
            $data['msg'] = 'Too many attempts.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Generate token and selector
        if ($cheap) {
            $selector = $this->token->generateCheap(12);
            $token = $this->token->generateCheap(32);
        } else {
            $selector = $this->token->generate(6);
            $token = $this->token->generate(16);
        }

        // Store token and selector in to database
        $q = $db->prepare('INSERT INTO ' . $tableName . ' (user_id, selector, token, expires) VALUES (?, ?, ?, ?)');
        $q->execute([$uid, $selector, hash('sha256', $token), $expirationTs]);

        // Err: Unable to store
        if ($q->rowCount() < 1) {

            // Try regenerate selector

            // Generate token and selector
            if ($cheap) {
                $selector = $this->token->generateCheap(12);
            } else {
                $selector = $this->token->generate(6);
            }

            // Store token and selector in to database
            $q = $db->prepare('INSERT INTO ' . $tableName . ' (user_id, selector, token, expires) VALUES (?, ?, ?, ?)');
            $q->execute([$uid, $selector, hash('sha256', $token), $expirationTs]);

            if ($q->rowCount() < 1) {
                $data['err'] = 4;
                $data['msg'] = 'Unexpected error, it is not possible to store token in to database.';
                return $data;
            }
        }

        // 1% chance to delete all expired tokens
        if ($this->config['autoDelete']) {
            $this->tokenDeleteExpired($tableName, 1);
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
     * ['err' => false, 'msg' => string, 'token' => string, 'selector' => string, 'expires' => timestamp]
     *
     * On error return array:
     * ['err' => int, 'msg' => string, 'token' => false, 'selector' => false, 'expires' => false]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Table name is not allowed
     * 3 - Any valid token exists
     *
     * @param int $uid
     * @param string $tableName
     * @return array
     */
    public function tokenUserHasValid($uid, $tableName)
    {
        // Default return data
        $data = [
            'err' => 1,
            'msg' => 'Unexpected error.',
        ];

        // Check table name
        if (!$this->tokenIsTableNameAllowed($tableName)) {
            // Err: Unapproved table name
            $data['err'] = 2;
            $data['msg'] = 'Table name is not allowed.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Try to get token from database
        $q = $db->prepare('SELECT expires FROM ' . $tableName . ' WHERE user_id = ? AND expires > UNIX_TIMESTAMP() LIMIT 1');
        $q->execute([$uid]);
        $expires = $q->fetchColumn();

        // Err: Any valid token exists
        if (empty($expires)) {
            $data['err'] = 3;
            $data['msg'] = 'Any valid token exists.';
            return $data;
        }

        // Return successful response
        $data['err'] = false;
        $data['expires'] = $expires;
        $data['msg'] = 'Valid tokens has been found.';
        return $data;
    }

    /**
     * At first check if user has enough attempts to perform validation.
     * Only then check if selector and token are valid.
     *
     * On success return array:
     * ['err' => false, 'msg' => string, 'uid' => int, 'token' => string, 'selector' => string, 'expires' => int]
     *
     * On error return array:
     * ['err' => int, 'msg' => string]
     *
     * On err 3+ add:
     * ['uid' => int, 'selector' => string, 'expires' => int]
     *
     * Error codes:
     * 1 - Unexpected error
     * 2 - Table name is not allowed
     * 3 - Selector does not exist
     * 4 - Token is expired
     * 5 - Tokens mismatch
     *
     * @param string $selector
     * @param string $token
     * @param string $tableName
     * @return array
     */
    public function tokenValidate($selector, $token, $tableName)
    {
        // Default return data
        $data = [
            'err' => 1,
            'msg' => 'Unexpected error.',
        ];

        // Check table name
        if (!$this->tokenIsTableNameAllowed($tableName)) {
            // Err: Unapproved table name
            $data['err'] = 2;
            $data['msg'] = 'Table name is not allowed.';
            return $data;
        }

        // Get connection
        $db = $this->connection->connect();

        // Search token by valid selector
        $q = $db->prepare('SELECT user_id, token, expires FROM ' . $tableName . ' WHERE selector = ?');
        $q->execute([$selector]);
        $dbToken = $q->fetch();

        // Selector does not exist or is expired
        if (!$dbToken) {
            $data['err'] = 3;
            $data['msg'] = 'Selector does not exist.';
            return $data;
        }

        // Set response data
        $data['uid'] = $dbToken['user_id'];
        $data['selector'] = $selector;
        $data['expires'] = $dbToken['expires'];

        // Check if token is not expired
        if ($dbToken['expires'] < time()) {
            $data['err'] = 4;
            $data['msg'] = 'Token is expired.';
            return $data;
        }

        // Are tokens equal?
        if (!$this->token->compare(hash('sha256', $token), $dbToken['token'])) {
            $data['err'] = 5;
            $data['msg'] = 'Tokens mismatch.';
            return $data;
        }

        // Set response data
        $data['err'] = false;
        $data['token'] = $token;
        $data['msg'] = 'Token is valid.';
        return $data;
    }

    /**
     * Delete token by selector
     * @param string $selector
     * @param string $tableName
     * @return bool
     */
    public function tokenDeleteBySelector($selector, $tableName)
    {
        // Check table name
        if (!$this->tokenIsTableNameAllowed($tableName)) {
            return false;
        }

        // Get connection
        $db = $this->connection->connect();

        // Delete expired tokens
        $q = $db->prepare('DELETE FROM ' . $tableName . ' WHERE selector = ?');
        $q->execute([$selector]);
        return true;
    }

    /**
     * Delete token by selector
     * @param string $uid
     * @param string $tableName
     * @return bool
     */
    public function tokenDeleteById($uid, $tableName)
    {
        // Check table name
        if (!$this->tokenIsTableNameAllowed($tableName)) {
            return false;
        }

        // Get connection
        $db = $this->connection->connect();

        // Delete expired tokens
        $q = $db->prepare('DELETE FROM ' . $tableName . ' WHERE user_id = ?');
        $q->execute([$uid]);
        return true;
    }

    /**
     * Delete all expired tokens from given table with given percent chance
     * @param string $tableName
     * @param int $chanceDelete
     * @return bool
     */
    private function tokenDeleteExpired($tableName, $chanceDelete = 100)
    {
        // Check table name
        if (!$this->tokenIsTableNameAllowed($tableName)) {
            return false;
        }

        if (rand(1, 100) <= $chanceDelete) {

            // Get connection
            $db = $this->connection->connect();

            // Delete expired tokens
            $q = $db->prepare('DELETE FROM ' . $tableName . ' WHERE expires <= UNIX_TIMESTAMP()');
            $q->execute();
            return true;
        }

        return false;
    }

    /**
     * @param string $tableName
     * @return bool
     */
    private function tokenIsTableNameAllowed($tableName)
    {
        return in_array($tableName, $this->allowedAuthTokenTableNames);
    }

    /**
     * Check if IP or user's ID is banned
     * @param $uid
     * @return bool
     */
    private function userIsBanned($uid = 0)
    {
        // Get IP of connected device
        $ip = $_SERVER['REMOTE_ADDR'];

        // Get connection
        $db = $this->connection->connect();

        // Is user banned?
        $q = $db->prepare('
            SELECT till_ts 
            FROM auth_users_ban 
            WHERE (user_id = ? OR ip_v4 = ?) 
            AND till_ts > UNIX_TIMESTAMP()
            ORDER BY till_ts DESC
            LIMIT 1
        ');
        $q->execute([$uid, $ip]);
        $r = $q->fetch();

        if ($r) {
            return $r['till_ts'];
        }

        return false;
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
}