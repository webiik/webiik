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
        'userGetAttempts' => [60, 3600],
        // Allow 30 sign-up attempts per 1 hour from same ip
        'userSetAttempts' => [30, 3600],
        // Allow 4 password renewals per 24 hours from same ip
        'userPswdAttempts' => [4, 86400],
        // Allow activate account or confirm password renewal within 24 hours
        // older records will be deleted
        'confirmationTime' => 86400,
        // Permanent login cookie name
        'cookieName' => 'PC',
        // Password salt
        'salt' => 'r489cjd3Xhed',
    ];

    public function __construct(Sessions $sessions, Connection $connection, Token $token, Attempts $attempts)
    {
        $this->sessions = $sessions;
        $this->connection = $connection;
        $this->token = $token;
        $this->attempts = $attempts;
    }

    /**
     * @param string $string
     */
    public function setSalt($string)
    {
        $this->config['salt'] = $string;
    }

    /**
     * @param string $string
     */
    public function setCookieName($string)
    {
        $this->config['cookieName'] = $string;
    }

    /**
     * @param int $days
     */
    public function setPermanent($days)
    {
        $this->config['permanent'] = $days;
    }

    /**
     * @param bool $bool
     */
    public function setWithActivation($bool)
    {
        $this->config['withActivation'] = $bool;
    }

    /**
     * @param int $count
     * @param int $sec
     */
    public function setUserGetAttempts($count, $sec)
    {
        $this->config['userGetAttempts'] = [$count, $sec];
    }

    /**
     * @param int $count
     * @param int $sec
     */
    public function setUserSetAttempts($count, $sec)
    {
        if($this->config['withActivation']) {
            // Sign-up, generate activation token and activate user
            $count = $count * 3;
        }
        $this->config['userSetAttempts'] = [$count, $sec];
    }

    /**
     * @param int $count
     * @param int $sec
     */
    public function setUserPswdAttempts($count, $sec)
    {
        // Generate renewal token and change password
        $count = $count * 2;
        $this->config['userPswdAttempts'] = [$count, $sec];
    }

    /**
     * @param int $sec
     */
    public function setConfirmationTime($sec)
    {
        $this->config['confirmationTime'] = $sec;
    }

    /**
     * @param int $uid
     */
    public function userLogin($uid)
    {
        $this->sessions->sessionRegenerateId();
        $this->sessions->addToSession('logged', $uid);

        if ($this->config['permanent'] > 0) {
            $this->userLoginPermanent($uid);
        }
    }

    /**
     * Destroy all sessions and delete permanent login cookie
     */
    public function userLogout()
    {
        $this->sessions->sessionDestroy();
        $this->userLogoutPermanent();
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * Return user id on success otherwise false
     * @return mixed
     */
    public function isUserLogged()
    {
        $uid = $this->sessions->getFromSession('logged');

        if ($uid) {
            return $uid;
        }

        $uid = $this->isUserPermanentlyLogged();

        if ($uid) {
            $this->userLogin($uid);
        }

        return $uid;
    }

    /**
     * Return true if user can do the action, otherwise false
     * @param string $action
     * @return bool
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
        $rows = $q->fetchAll();

        // Check if user can do the action
        foreach ($rows as $row) {
            if ($row['action'] == $action) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sign up the user in to database
     * On too many attempts return -3
     * If user already exists return -2
     * On success without activation return array with 'uid'
     * On success with activation return array with 'uid', 'selector', 'token'
     * @param string $email
     * @param string $pswd
     * @param int $roleId
     * @return int|array
     */
    public function userSet($email, $pswd, $roleId)
    {
        $attemptsLimit = $this->config['userSetAttempts'][0];
        $attemptsInterval = $this->config['userSetAttempts'][1];

        // Return false when there is too many sign up attempts from current ip
        $attemptsCount = $this->attempts->getAttemptsCount('signup', $attemptsInterval);
        if ($attemptsCount >= $attemptsLimit) {
            return -3;
        }

        // Store sign up attempt of current ip, agent
        $this->attempts->setAttempt('signup');

        // 10% chance to delete old attempts during sign-up
        if (rand(1, 100) <= 10) {
            $this->attempts->deleteAttempts('signup', $attemptsInterval);
        }

        // If sign up is with activation, delete expired users
        if ($this->config['withActivation']) {
            $this->deleteExpiredActivations();
        }

        // Store user in database
        $db = $this->connection->connect();
        $q = $db->prepare('INSERT INTO auth_users (role_id, email, pswd, signup_ts) VALUES (?, ?, ?, UNIX_TIMESTAMP())');
        $q->execute([$roleId, $email, hash_hmac('sha256', $pswd, $this->config['salt'])]);

        // Unable to store user in database, user probably already exists
        $uid = $q->rowCount() > 0 ? $db->lastInsertId() : false;
        if (!$uid) {
            return -2;
        }

        // If sign up is with activation, generate and store activation token for current sign up
        if ($this->config['withActivation']) {
            $data = $this->userGenerateActivationToken($uid);
        }

        $data['uid'] = $uid;

        return $data;
    }

    /**
     * Get user from database
     * On too many attempts return -3
     * If user does not exist return -2
     * If user is expired return -1
     * If password does not match return 0
     * With activation on success return array with 'uid' and 'status' [0 - not activated, 1 - activated]
     * Without activation on success return array with 'uid'
     * @param $email
     * @param $pswd
     * @return int
     */
    public function userGet($email, $pswd)
    {
        $attemptsLimit = $this->config['userGetAttempts'][0];
        $attemptsInterval = $this->config['userGetAttempts'][1];

        // Return false when there is too many sign up attempts from current ip
        $attemptsCount = $this->attempts->getAttemptsCount('login', $attemptsInterval);
        if ($attemptsCount >= $attemptsLimit) {
            return -3;
        }

        // Store log in attempt of current ip, agent
        $this->attempts->setAttempt('login');

        // 5% chance to delete old attempts during login
        if (rand(1, 100) <= 5) {
            $this->attempts->deleteAttempts('login', $attemptsInterval);
        }

        // Get user from database
        $db = $this->connection->connect();
        if ($this->config['withActivation']) {
            $q = $db->prepare('
                SELECT 
                au.id, 
                au.pswd,
                (SELECT COUNT(*) FROM auth_users_activated WHERE user_id = au.id) AS activated,
                (SELECT COUNT(*) FROM auth_tokens_activation WHERE user_id = au.id AND expires > UNIX_TIMESTAMP()) AS trial
                FROM auth_users au
                WHERE au.email = ?
            ');
        } else {
            $q = $db->prepare('SELECT id, pswd FROM auth_users WHERE email = ?');
        }
        $q->execute([$email]);
        $r = $q->fetch();

        // User does not exist
        if (count($r) < 1) {
            return -2;
        }

        // User exists but is expired
        if ($this->config['withActivation'] && $r['activated'] == 0 && $r['trial'] == 0) {
            return -1;
        }

        // User exists but password does not match
        if (!$this->token->compare(hash_hmac('sha256', $pswd, $this->config['salt']), $r['pswd'])) {
            return 0;
        }

        // User is activated
        if ($this->config['withActivation'] && $r['activated'] > 0) {
            $data['status'] = 1;
        }

        // User is not activated
        if ($this->config['withActivation'] && $r['activated'] == 0) {
            $data['status'] = 0;
        }

        $data['uid'] = $r['id'];

        return $data;
    }

    /**
     * Activate the user
     * @param $selector
     * @param $token
     * @return bool
     */
    public function userActivate($selector, $token)
    {
        $attemptsLimit = $this->config['userSetAttempts'][0];
        $attemptsInterval = $this->config['userSetAttempts'][1];

        // Return false when there is too many activation attempts from current ip
        $attemptsCount = $this->attempts->getAttemptsCount('signup', $attemptsInterval);
        if ($attemptsCount >= $attemptsLimit) {
            return false;
        }

        // Store activation attempt of current ip, agent
        $this->attempts->setAttempt('signup');

        // 5% chance to delete old attempts during activation
        if (rand(1, 100) <= 5) {
            $this->attempts->deleteAttempts('signup', $attemptsInterval);
        }

        $db = $this->connection->connect();

        $q = $db->prepare('
            SELECT user_id, token
            FROM auth_tokens_activation 
            WHERE selector = ? AND expires > UNIX_TIMESTAMP()
            LIMIT 1
        ');
        $q->execute([$selector]);
        $r = $q->fetch();

        // Selector does not exist or is expired
        if (count($r) < 1) {
            return false;
        }

        // Are tokens equal?
        $token = hash('sha256', $token);
        if (!$this->token->compare($token, $r['token'])) {
            return false;
        }

        // Insert user activation
        $q = $db->prepare('INSERT INTO auth_users_activated (user_id) VALUES (?)');
        $q->execute([$r['user_id']]);

        // Unable to insert activation
        if ($q->rowCount() < 1) {
            return false;
        }

        // Delete auth token
        $q = $db->prepare('DELETE FROM auth_tokens_activation WHERE user_id = ?');
        $q->execute([$r['user_id']]);

        return true;
    }

    /**
     * Generate activation tokens for specified user id
     * On too many attempts return -3
     * On db error return -2
     * On success return array with 'selector' and 'token'
     * @param $uid
     * @return array|int
     */
    public function userGenerateActivationToken($uid)
    {
        $attemptsLimit = $this->config['userSetAttempts'][0];
        $attemptsInterval = $this->config['userSetAttempts'][1];

        // Return false when there is too many sign up attempts from current ip
        $attemptsCount = $this->attempts->getAttemptsCount('signup', $attemptsInterval);
        if ($attemptsCount >= $attemptsLimit) {
            return -3;
        }

        // Store sign up attempt of current ip, agent
        $this->attempts->setAttempt('signup');

        // 5% chance to delete old attempts during sign-up
        if (rand(1, 100) <= 5) {
            $this->attempts->deleteAttempts('signup', $attemptsInterval);
        }

        // Generate activation tokens
        $data = [];
        $data['selector'] = $this->token->generate(6);
        $data['token'] = $this->token->generate();

        // Store activation tokens
        $expires = $this->config['confirmationTime'];
        $db = $this->connection->connect();
        $q = $db->prepare('INSERT INTO auth_tokens_activation (user_id, selector, token, expires) VALUES (?, ?, ?, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL ? SECOND)))');
        $q->execute([$uid, $data['selector'], hash('sha256', $data['token']), $expires]);

        // Unable to store activation tokens
        if ($q->rowCount() < 1) {
            return -2;
        }

        // Return activation tokens
        return $data;
    }

    /**
     * Change user password
     * @param $selector
     * @param $token
     * @param $pswd
     * @return bool
     */
    public function userChangePassword($selector, $token, $pswd)
    {
        $attemptsLimit = $this->config['userPswdAttempts'][0];
        $attemptsInterval = $this->config['userPswdAttempts'][1];

        // Return false when there is too many attempts from current ip
        $attemptsCount = $this->attempts->getAttemptsCount('renewal', $attemptsInterval);
        if ($attemptsCount >= $attemptsLimit) {
            return false;
        }

        // Store attempt of current ip, agent
        $this->attempts->setAttempt('renewal');

        // 5% chance to delete old attempts
        if (rand(1, 100) <= 5) {
            $this->attempts->deleteAttempts('renewal', $attemptsInterval);
        }

        $db = $this->connection->connect();

        $q = $db->prepare('
            SELECT email, token
            FROM auth_tokens_renewal 
            WHERE selector = ? AND expires > UNIX_TIMESTAMP()
            LIMIT 1
        ');
        $q->execute([$selector]);
        $r = $q->fetch();

        // Selector does not exist or is expired
        if (count($r) < 1) {
            return false;
        }

        // Are tokens equal?
        $token = hash('sha256', $token);
        if (!$this->token->compare($token, $r['token'])) {
            return false;
        }

        // Update user password
        $q = $db->prepare('UPDATE auth_users SET pswd = ? WHERE email = ?');
        $q->execute([$pswd, $r['email']]);

        // Unable to update email, probably user does not exist
        if ($q->rowCount() < 1) {
            return false;
        }

        // Delete all user's auth tokens
        $q = $db->prepare('DELETE FROM auth_tokens_renewal WHERE email = ?');
        $q->execute([$r['email']]);

        return true;
    }

    /**
     * Generate password renewal tokens for specified email
     * On too many attempts return -3
     * On db error return -2
     * On success return array with 'selector' and 'token'
     * @param $email
     * @return array|int
     */
    public function userGeneratePasswordToken($email)
    {
        $attemptsLimit = $this->config['userPswdAttempts'][0];
        $attemptsInterval = $this->config['userPswdAttempts'][1];

        // Return false when there is too many attempts from current ip
        $attemptsCount = $this->attempts->getAttemptsCount('renewal', $attemptsInterval);
        if ($attemptsCount >= $attemptsLimit) {
            return -3;
        }

        // Store attempt of current ip, agent
        $this->attempts->setAttempt('renewal');

        // 5% chance to delete old attempts
        if (rand(1, 100) <= 5) {
            $this->attempts->deleteAttempts('renewal', $attemptsInterval);
        }

        // 5% chance to delete old expired renewal tokens
        if (rand(1, 100) <= 5) {
            $this->deleteExpiredRenewalTokens();
        }

        // Generate activation tokens
        $data = [];
        $data['selector'] = $this->token->generate(6);
        $data['token'] = $this->token->generate();

        // Store activation tokens
        $expires = $this->config['confirmationTime'];
        $db = $this->connection->connect();
        $q = $db->prepare('INSERT INTO auth_tokens_renewal (email, selector, token, expires) VALUES (?, ?, ?, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL ? SECOND)))');
        $q->execute([$email, $data['selector'], hash('sha256', $data['token']), $expires]);

        // Unable to store activation tokens
        if ($q->rowCount() < 1) {
            return -2;
        }

        // Return activation tokens
        return $data;
    }

    /**
     * Store auth credentials for permanent login
     * Return true on success otherwise false
     * @param int $uid
     * @return bool
     */
    private function userLoginPermanent($uid)
    {
        $pdo = $this->connection->connect();

        // 5% chance to delete old expired permanent login tokens
        if ($this->config['permanent'] > 0 && rand(1, 100) <= 5) {
            $this->deleteExpiredPermanentTokens();
        }

        // User can't have more than 5 permanent connections
        $q = $pdo->prepare('SELECT COUNT(*) FROM auth_tokens_permanent WHERE user_id = ?');
        $q->execute([$uid]);
        if ($q->fetchColumn() >= 5) {
            return false;
        }

        // Prepare selector and token
        $selector = $this->token->generate(6);
        $token = $this->token->generate();

        // Store auth credentials
        $q = $pdo->prepare('INSERT INTO auth_tokens_permanent (user_id, selector, token, expires) VALUES (?, ?, ?, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL ? DAY)))');
        $q->execute([$uid, $selector, hash('sha256', $token), $this->config['permanent']]);
        if ($q->rowCount() < 1) {
            return false;
        }

        // Create auth cookie
        $this->sessions->setCookie($this->config['cookieName'], $selector . ':' . $token, $this->config['permanent'] . ' days');

        return true;
    }

    /**
     * Delete all records in table auth_permanent associated with current user id.
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
            $pdo = $this->connection->connect();
            $q = $pdo->prepare('DELETE FROM auth_tokens_permanent WHERE user_id = ?');
            $q->execute([$uid]);
        }
    }

    /**
     * Delete expired auth tokens from database
     * @return bool|int
     */
    private function deleteExpiredPermanentTokens()
    {
        $pdo = $this->connection->connect();
        $q = $pdo->query('DELETE FROM auth_tokens_permanent WHERE expires < UNIX_TIMESTAMP()');
        return $q->rowCount();
    }

    /**
     * Delete expired renewal tokens from database
     * @return bool|int
     */
    private function deleteExpiredRenewalTokens()
    {
        $pdo = $this->connection->connect();
        $q = $pdo->query('DELETE FROM auth_tokens_renewal WHERE expires < UNIX_TIMESTAMP()');
        return $q->rowCount();
    }

    /**
     * Delete expired users and activation tokens from database
     */
    private function deleteExpiredActivations()
    {
        $db = $this->connection->connect();

        // Get user ids of expired users
        $uidToDelete = [];
        $q = $db->query('SELECT user_id FROM auth_tokens_activation WHERE expires < UNIX_TIMESTAMP()');
        $r = $q->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($r as $row) {
            $uidToDelete[] = $row['user_id'];
        }

        // Delete expired tokens
        $db->query('DELETE FROM auth_tokens_activation WHERE expires < UNIX_TIMESTAMP()');

        // Delete expired users
        $q = $db->prepare('DELETE FROM auth_users WHERE id IN (?)');
        $q->execute([implode(',', $uidToDelete)]);
    }

    /**
     * Return user id when user is permanently logged, otherwise false
     * @return mixed
     */
    private function isUserPermanentlyLogged()
    {
        // Does permanent login cookie exist?
        if (!$this->sessions->getCookie($this->config['cookieName'])) {
            return false;
        }

        // Is permanent login cookie valid?
        $cookie = explode(':', $this->sessions->getCookie($this->config['cookieName']));
        if (!isset($cookie[0]) || !isset($cookie[1])) {
            return false;
        }

        // Get DB record for current selector
        $pdo = $this->connection->connect();
        $selector = $cookie[0];
        $q = $pdo->prepare('SELECT user_id, token FROM auth_tokens_permanent WHERE selector = ? AND expires > UNIX_TIMESTAMP() LIMIT 1');
        $q->execute([$selector]);
        $row = $q->fetch();

        // Selector does not exist or is expired
        if (count($row) < 1) {
            return false;
        }

        // Are tokens equal?
        $token = hash('sha256', $cookie[1]);
        if (!$this->token->compare($token, $row['token'])) {
            return false;
        }

        return $row['user_id'];
    }
}