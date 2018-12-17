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
class AuthExtended extends Auth
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Attempts
     */
    private $attempts;

    /**
     * Auth configuration
     * @var $config
     */
    private $config = [
        // Require account activation or not
        'withActivation' => false,
        // Number of attempt to login, sign-up, token actions
        // Allow 60 attempts per 1 hour from same ip
        'attemptsLimit' => [60, 3600],
        // Automatically delete expired tokens and attempts
        'autoDelete' => true,
        // Allow validate token within 24 hours
        'confirmationTime' => 86400,
        // Account resolution mode read more at setUserAccountResolution
        'accountResolutionMode' => 0,
        // Account suffix
        'suffix' => '',
        // Password salt
        'salt' => 'r489cjd3Xhed',
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
        parent::__construct($cookie, $session, $token);
        $this->connection = $connection;
        $this->attempts = $attempts;
    }

    /**
     * Set salt
     * @param string $string
     */
    public function setSalt($string)
    {
        $this->config['salt'] = $string;
    }

    /**
     * Set current suffix
     * @param string $string
     */
    public function setSuffix($string)
    {
        $this->config['suffix'] = $string;
    }

    /**
     * Distinguish user accounts by suffix
     * 0 - user can make only one account and access this account with every or no suffix
     * 1 - user can make only one account and access this account only with specific suffix
     * 2 - user can make multiple accounts and access these accounts only with specific suffix
     * @param int $modeNum
     */
    public function setUserAccountResolution($modeNum)
    {
        $this->config['accountResolutionMode'] = $modeNum;
    }

    /**
     * Configure if sign-up needs activation or not
     * @param bool $bool
     */
    public function setWithActivation($bool)
    {
        $this->config['withActivation'] = $bool;
    }

    /**
     * Configure attempts limit per time period from same IP
     * @param int $count
     * @param int $sec
     */
    public function setAttemptsLimit($count, $sec)
    {
        $this->config['attemptsLimit'] = [$count, $sec];
    }

    /**
     * Configure how much time has user to confirm activation or password renewal
     * @param int $sec
     */
    public function setConfirmationTime($sec)
    {
        $this->config['confirmationTime'] = $sec;
    }

    /**
     * Return user id if user can do the action, otherwise false
     * @param string $action
     * @return mixed
     */
    public function userCan($action)
    {
        $uid = $this->isLogged();

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
        $r = $q->fetchAll(\PDO::FETCH_ASSOC);

        // Check if user can do the action
        foreach ($r as $row) {
            if ($row['action'] == $action) {
                return $uid;
            }
        }

        return false;
    }

    /**
     * Set user to database
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
     * 3 - IP is banned
     * 4 - Token error
     * 5 - User already exists
     * 6 - User already exists under different suffix.
     * 7 - User with pending activation already exists.
     * 8 - User with pending activation already exists under different suffix.
     * 9 - Unexpected error, it is unable to store user in social database.
     * 10 - Unexpected error, it is unable to store user in database.
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
            $data['err'] = 3;
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

        // Check if activated user already exists
        $r = $this->userExistsActivated($email);

        if ($r['s'] == 1) {
            $data['err'] = 5;
            $data['msg'] = 'User already exists.';
            return $data;
        }

        if ($r['s'] == 2) {
            $data['err'] = 6;
            $data['msg'] = 'User already exists under different suffix.';
            $data['suffix'] = $r['suffix'];
            return $data;
        }

        // Do following only when account activation is required
        if (!$this->config['withActivation']) {

            // Set default user status to active
            $status = 1;

        } else {

            // Set default user status to inactive
            $status = 0;

            // Check if there is some user with same email and pending activation
            $r = $this->userExistsPending($email);

            if ($r['s'] == 2) {
                $data['err'] = 8;
                $data['msg'] = 'User with pending activation already exists under different suffix.';
                $data['suffix'] = $r['suffix'];
                return $data;
            }

            if ($r['s'] == 1) {
                // Err: User with pending activation already exists

                // Every user with pending activation should have valid re-activation token too
                $token = $this->tokenUserHasValid($r['id'], 'auth_tokens_re_activation');
                if ($token['err']) {
                    // Err: Unable to get any re-activation token

                    // Mark user as expired
                    $this->userInactiveToExpired($r['id']);

                    $data['err'] = 4;
                    $data['msg'] = $token['msg'];
                    return $data;
                }

                // Generate new re-activation token
                $token = $this->tokenGenerate($r['id'], 'auth_tokens_re_activation', $token['expires']);

                // Err: Unable to generate re-activation token
                if ($token['err']) {
                    $data['err'] = 4;
                    $data['msg'] = $token['msg'];
                    return $data;
                }

                // Err: User with pending activation exists
                $data['tokens']['re-activation']['token'] = $token['token'];
                $data['tokens']['re-activation']['selector'] = $token['selector'];
                $data['tokens']['re-activation']['expires'] = $token['expires'];
                $data['err'] = 7;
                $data['msg'] = 'User with pending activation already exists.';
                return $data;

            } else {

                // We are here, it means that we may have some expired inactivated
                // users with status 0, so update theirs status to expired
                $this->userInactiveToExpired($email);
            }
        }

        // Set empty password if user is signed up via social
        if ($provider) {
            $pswd = '';
        } else {
            $pswd = hash_hmac('sha256', $pswd, $this->config['salt']);
        }

        // Add user in to table auth_users
        $uid = $this->userAdd($roleId, $email, $pswd, $status);

        // Err: Unexpected error, we don't have user id
        if (!$uid) {
            $data['err'] = 10;
            $data['msg'] = 'Unexpected error, it is unable to store user in database.';
            return $data;
        }

        // Set response data user id value
        $data['uid'] = $uid;

        // Get database connection
        $db = $this->connection->connect();

        // If it's social sign-up, add user also to table auth_users_social
        if ($provider) {
            $q = $db->prepare('INSERT INTO auth_users_social (provider, user_id) VALUES (?, ?)');
            $q->execute([$provider, $uid]);

            if ($q->rowCount() < 1) {
                $data['err'] = 9;
                $data['msg'] = 'Unexpected error, it is unable to store user in social database.';
                return $data;
            }

            $data['provider'] = $provider;
        }

        // Generate activation and re-activation token when activation is required
        if ($this->config['withActivation']) {

            $tokenExpirationTs = time() + $this->config['confirmationTime'];

            $token = $this->tokenGenerate($uid, 'auth_tokens_re_activation', $tokenExpirationTs);

            // Err: Unable to generate token
            if ($token['err']) {
                $data['err'] = 4;
                $data['msg'] = $token['msg'];
                return $data;
            }

            $data['tokens']['re-activation']['token'] = $token['token'];
            $data['tokens']['re-activation']['selector'] = $token['selector'];
            $data['tokens']['re-activation']['expires'] = $token['expires'];

            $token = $this->tokenGenerate($uid, 'auth_tokens_activation', $tokenExpirationTs);

            // Err: Unable to generate token
            if ($token['err']) {
                $data['err'] = 4;
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
     * 1 - Unexpected error.
     * 2 - Too many attempts.
     * 3 - User is banned.
     * 4 - Token error.
     * 5 - User does not exist.
     * 6 - User exists but under different suffix.
     * 7 - User isn't activated, but can be (re)activated.
     * 8 - User previously logged in via social account and did not set password for 'classic' login.
     * 9 - Social account is not paired with main account.
     * 10 - Unexpected error, more users exist.
     * 11 - Incorrect password.
     * 12 - Password does not match.
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
            $data['err'] = 11;
            $data['msg'] = 'Incorrect password.';
            return $data;
        }

        // Check if valid user exists
        $user = $this->userExistsValid($email);
        $validUsersCount = $user['count'];

        // Valid user exists
        if ($user['s'] == 1 && $validUsersCount == 1) {

            // Set user id value
            $data['uid'] = $user['id'];

            // Is user banned?
            if ($banned = $this->userIsBanned($user['id'])) {
                // Err: User is banned
                $data['err'] = 3;
                $data['ts'] = $banned['till_ts'];
                $data['msg'] = 'User is banned.';
                return $data;
            }
        }

        // Valid user exists, but is not activated
        if ($user['s'] == 1 && $validUsersCount == 1 && $user['status'] == 0) {

            if (!$this->config['withActivation']) {

                // We can't count user with status 0 as valid if activation is not required.
                $validUsersCount = 0;

            } else {

                // There is no activated user, but inactive user exists,
                // so offer to this user to get activation token again

                // Try to get existing valid re-activation token by user id
                $token = $this->tokenUserHasValid($user['id'], 'auth_tokens_re_activation');

                // If there is existing valid re-activation token, use expiration date of this token
                if (!$token['err']) {
                    $expires = $token['expires'];
                } else {
                    $expires = time() + $this->config['confirmationTime'];
                }

                // Generate new re-activation token
                $token = $this->tokenGenerate($user['id'], 'auth_tokens_re_activation', $expires);

                // Err during generating token
                if ($token['err']) {
                    $data['err'] = 4;
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
            $data['err'] = 10;
            $data['msg'] = 'Unexpected error, more users exist.';
            return $data;
        }

        // Err: User does not exist
        if ($user['s'] == 0 || $validUsersCount == 0) {
            $data['err'] = 5;
            $data['msg'] = 'User does not exist.';
            return $data;
        }

        // Get database connection
        $db = $this->connection->connect();

        // Check password, but only if it's not social login
        if (!$provider && !$this->token->compare(hash_hmac('sha256', $pswd, $this->config['salt']), $user['pswd'])) {

            // Password does not match

            // But if there is no user password in DB,
            // check if previously user didn't log in via social...
            if ($user['pswd'] == '') {

                // Does user exists in auth_users_social
                $q = $db->prepare('SELECT provider FROM auth_users_social WHERE user_id = ?');
                $q->execute([$user['id']]);
                $providers = $q->fetchAll(\PDO::FETCH_ASSOC);

                if ($providers) {
                    // Err: User previously logged via social and did not set password for 'classic' login
                    $data['err'] = 8;
                    $data['msg'] = 'User previously logged in via social account and did not set password for \'classic\' login.';
                    $data['providers'] = [];
                    foreach ($providers as $key => $arr) {
                        $data['providers'][] = ucfirst($arr['provider']);
                    }
                    return $data;
                }
            }

            // Err: Password isn't correct
            $data['err'] = 12;
            $data['msg'] = 'Password does not match.';
            return $data;
        }

        // Err: User exists but under different suffix.
        if ($user['s'] == 2) {
            $data['err'] = 6;
            $data['msg'] = 'User exists but under different suffix.';
            $data['suffix'] = $user['suffix'];
            return $data;
        }

        // Err: User isn't activated, but can be (re)activated
        if (isset($data['tokens'])) {
            $data['err'] = 7;
            $data['msg'] = 'User isn\'t activated, but can be (re)activated.';
            return $data;
        }

        // Check if user is stored in auth_users_social, but only if it is social login
        if ($provider) {

            // Does user exists in auth_users_social
            $q = $db->prepare('SELECT COUNT(*) FROM auth_users_social WHERE user_id = ? AND provider = ?');
            $q->execute([$user['id'], $provider]);

            // User never logged in with current provider, but account with same email already exists
            // You can offer to pair current provider with existing account
            // Err: User already exists in auth_users but does not exist in auth_users_social
            if ($q->fetchColumn() == 0) {

                // Try to get existing valid re-pairing token by user id
                $token = $this->tokenUserHasValid($user['id'], 'auth_tokens_re_pairing_' . $provider);

                // If there is existing valid re-pairing token, use expiration date of this token
                if (!$token['err']) {
                    $expires = $token['expires'];
                } else {
                    $expires = time() + $this->config['confirmationTime'];
                }

                // Generate new re-pairing token
                $token = $this->tokenGenerate($user['id'], 'auth_tokens_re_pairing_' . $provider, $expires);

                // Problem during generating token
                if ($token['err']) {
                    $data['err'] = 4;
                    $data['msg'] = $token['msg'];
                }

                // Err: User already exists in auth_users but does not exist in auth_users_social
                $data['tokens']['re-pairing']['provider'] = $provider;
                $data['tokens']['re-pairing']['token'] = $token['token'];
                $data['tokens']['re-pairing']['selector'] = $token['selector'];
                $data['tokens']['re-pairing']['expires'] = $token['expires'];
                $data['err'] = 9;
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
     * 5 - Email exist but under different suffix
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
        $r = $this->userExistsActivated($email);

        if ($r['s'] == 2) {
            // Err: Too many attempts
            $data['err'] = 5;
            $data['msg'] = 'Email exist but under different suffix.';
            $data['suffix'] = $r['suffix'];
            return $data;
        }

        if ($r['s'] == 1) {
            // We have found valid user associated with provided email
            $uid = $r['id'];
        }

        // If we didn't find activated user, but activation is required,
        // there is still chance that there can be user with pending activation.
        if (!$uid && $this->config['withActivation']) {

            // Accounts require activation...
            $r = $this->userExistsPending($email);

            if ($r['s'] == 1) {
                // We have found valid user associated with provided email
                $uid = $r['id'];
            }
        }

        if (!$uid) {
            // Err: There is no valid user for provided email
            $data['err'] = 3;
            $data['msg'] = 'User does not exist.';
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
        if ($db->errorCode() != 0 && $q->rowCount() < 1) {
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
        $r = $this->userExistsActivatedByTokenUid($validatedToken['uid']);

        if ($r['s'] == 1) {
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
     * @param $user_id
     * @return bool
     */
    private function userIsBanned($user_id = 0)
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
        $q->execute([$user_id, $ip]);
        $r = $q->fetch();

        if ($r) {
            return $r['till_ts'];
        }

        return false;
    }

    /**
     * Check if user exists
     * $q1 - Query to find user in ARM 0, or ARM 1 and !$onlyInCurrentSuffix
     * $q2 - Query to find user in ARM 2, or ARM 1 and $onlyInCurrentSuffix
     *
     * @param array $data
     * @param string $mysqlQuery
     * @return array
     */
    private function userExists($data, $mysqlQuery)
    {

        $db = $this->connection->connect();

        $res = [
            // 0 - user doesn't exist
            // 1 - user exists
            // 2 - user exists but in different suffix
            's' => 0,
            // Number of found users
            'count' => 0,
        ];

        $q = $db->prepare($mysqlQuery);

        $q->execute($data);


        if ($r = $q->fetchAll(\PDO::FETCH_ASSOC)) {
            $res['id'] = $r[0]['id'];
            $res['pswd'] = $r[0]['pswd'];
            $res['status'] = $r[0]['status'];
            $res['count'] = count($r);
            $res['suffix'] = $r[0]['suffix'];

            // In ARM 0 it doesn't matter the suffix, we can always mark user just like existing
//            if ($this->config['accountResolutionMode'] == 0) {
                $res['s'] = 1;
//            }

            // In ARM 1 it the suffix determines if user exists in current or different suffix
            if ($this->config['accountResolutionMode'] == 1) {
                $res['s'] = $this->config['suffix'] == $r[0]['suffix'] ? 1 : 2;
            }

            // In ARM 2 the suffix determines if user exists in current suffix
//            if ($this->config['accountResolutionMode'] == 2) {
//                $res['s'] = $this->config['suffix'] == $r[0]['suffix'] ? 1 : 0;
//            }
        }

        return $res;
    }

    /**
     * Check if activated user exists
     * @param int $uid
     * @return array
     */
    private function userExistsActivatedByTokenUid($uid)
    {
        $data[] = $uid;
        $query = 'SELECT auth_users.id, auth_users.status, auth_users.pswd, auth_users_suffix.suffix
                FROM auth_users
                LEFT JOIN auth_users_suffix
                ON auth_users_suffix.user_id = auth_users.id
                WHERE status = 1 
                AND email = (SELECT email FROM auth_users WHERE id = ?)';

        // In ARM 2 we can have more users, so we need to recognise them by suffix
        if ($this->config['accountResolutionMode'] == 2) {
            $data[] = $this->config['suffix'];
            $query .= 'AND suffix = ?';
        }

        return $this->userExists([$uid], $query);
    }

    /**
     * Check if activated user exists
     * @param string $email
     * @return array
     */
    private function userExistsActivated($email)
    {
        $data[] = $email;
        $query = 'SELECT auth_users.id, auth_users.status, auth_users.pswd, auth_users_suffix.suffix
                FROM auth_users
                LEFT JOIN auth_users_suffix
                ON auth_users_suffix.user_id = auth_users.id
                WHERE status = 1
                AND email = ?';

        // In ARM 2 we can have more users, so we need to recognise them by suffix
        if ($this->config['accountResolutionMode'] == 2) {
            $data[] = $this->config['suffix'];
            $query .= 'AND suffix = ?';
        }

        return $this->userExists($data, $query);
    }

    /**
     * Check if valid user exists. Valid user can be inactive or activated user.
     * @param string $email
     * @return array
     */
    private function userExistsValid($email)
    {
        $data[] = $email;
        $query = 'SELECT auth_users.id, auth_users.status, auth_users.pswd, auth_users_suffix.suffix
                FROM auth_users
                LEFT JOIN auth_users_suffix
                ON auth_users_suffix.user_id = auth_users.id
                WHERE status <= 1            
                AND email = ?';

        // In ARM 2 we can have more users, so we need to recognise them by suffix
        if ($this->config['accountResolutionMode'] == 2) {
            $data[] = $this->config['suffix'];
            $query .= 'AND suffix = ?';
        }

        return $this->userExists($data, $query);
    }

    /**
     * Check if pending user exists
     * @param string $email
     * @return array
     */
    private function userExistsPending($email)
    {
        $data[] = $email;
        $query = 'SELECT au.id, au.status, au.pswd, aus.suffix
                FROM auth_users au
                LEFT JOIN auth_tokens_activation ata
                ON ata.user_id = au.id
                LEFT JOIN auth_users_suffix aus
                ON aus.user_id = au.id       
                WHERE ata.expires > UNIX_TIMESTAMP()
                AND au.status = 0
                AND au.email = ?';

        // In ARM 2 we can have more users, so we need to recognise them by suffix
        if ($this->config['accountResolutionMode'] == 2) {
            $data[] = $this->config['suffix'];
            $query .= 'AND suffix = ?';
        }

        return $this->userExists($data, $query);
    }

    /**
     * Change status to expired for inactive user(s) with given $val or and $suffix in ARM 2
     * @param $val - email or uid
     */
    private function userInactiveToExpired($val)
    {
        $column = is_numeric($val) ? 'auth_users.id' : 'email';
        $db = $this->connection->connect();

        if ($this->config['accountResolutionMode'] == 2) {

            $q = $db->prepare('
                UPDATE auth_users 
                LEFT JOIN auth_users_suffix
                ON auth_users_suffix.user_id = auth_users.id
                SET status = 2
                WHERE status = 0
                AND suffix = ?
                AND ' . $column . ' = ?               
            ');
            $q->execute([$this->config['suffix'], $val]);

        } else {

            $q = $db->prepare('UPDATE auth_users SET status = 2 WHERE status = 0 AND ' . $column . ' = ?');
            $q->execute([$val]);
        }
    }

    /**
     * @param int $roleId
     * @param string $email
     * @param string $pswd
     * @param int $status
     * @return bool|int
     */
    private function userAdd($roleId, $email, $pswd, $status)
    {
        $arm = $this->config['accountResolutionMode'];

        if (($arm == 1 || $arm == 2) && $this->config['suffix'] == '') {
            return false;
        }

        // Add user in to table auth_users
        $db = $this->connection->connect();

        $q = $db->prepare('
            INSERT INTO auth_users 
            (role_id, email, pswd, signup_ts, status) 
            VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)
        ');
        $q->execute([$roleId, $email, $pswd, $status]);

        // Try to get user id
        $uid = $q->rowCount() > 0 ? $db->lastInsertId() : false;

        if (!$uid) {
            return false;
        }

        // If we have defined allowed suffixes, add them to specific user
        if ($this->config['suffix'] != '') {
            $q = $db->prepare('INSERT INTO auth_users_suffix (user_id, suffix) VALUES (?, ?)');
            $q->execute([$uid, $this->config['suffix']]);
        }

        return $uid;
    }
}