<?php
namespace Webiik;

class Authentication
{
    /** @var \PDO @inject */
    private $pdo;

    /** @var Validate @inject */
    private $validate;

    /** @var Token @inject */
    private $token;

    /** @var Sessions @inject */
    private $sessions;

    /** @var Request @inject */
    private $req;

    // Is login permanent or not?
    private $permanentLogin = false;
    private $permanentLoginDuration = '2 weeks';

    // Where will be user redirected after login nad logout
    // These params can be overridden fn param
    private $urlAfterLogin;
    private $urlAfterLogout;

    public function setPermanentLogin($bool, $strtotime = null)
    {
        $this->permanentLogin = $bool;
        if($strtotime) $this->permanentLoginDuration = $strtotime;
    }

    public function setUrlAfterLogin($urlAfterLogin)
    {
        $this->urlAfterLogin = $urlAfterLogin;
    }

    public function setUrlAfterLogout($urlAfterLogout)
    {
        $this->urlAfterLogout = $urlAfterLogout;
    }

    /**
     * Return
     * true - successful login
     * 0 - inactive account
     * 1 - too many login attempts, login denied
     * 2 - wrong email
     * 3 - wrong passpord
     *
     * @return bool
     * @throws \Exception
     */
    public function loginEmailPswd($email, $pswd, $permanent = null, $strtotime = null)
    {
        if(!$permanent) $permanent = $this->permanentLogin;
        if(!$strtotime) $strtotime = $this->permanentLoginDuration;

        // Log login attempt
        // Are there many login attempts? Deny user login and flash it.
        if ($this->attempt($email, 30, '5 minutes', '30 minutes')) return 1;

        // Validate inputs
        // Are some inputs invalid? Flash message it.
        $email = $this->validate->email($email);
        $pswd = $this->validate->pswd($pswd, 6, 2);
        if (!$email) return 2;
        if (!$pswd) return 3;


        // Validate inputs in DB
        $q = $this->pdo->prepare('SELECT id FROM users WHERE email = ? AND status = 0 OR status = 1');
        $q->execute([$email]);
        $row = $q->fetch();
        if (count($row) < 1) return 2;
        if ($row['status'] == 0) return 0;


        $q = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND pswd = ? AND status = 1');
        $q->execute([$email, $pswd]);
        $row = $q->fetch();
        if (count($row) < 1) return 3;

        $uid = $row['id'];

        // Was checked permanent login? Login user permanently
        // Login user
        $this->login($uid, $permanent, $strtotime);

        return true;
    }

    public function login($uid, $permanent = null, $strtotime = null)
    {
        session_regenerate_id();
        $_SESSION['logged'] = $uid;

        if(!$permanent) $permanent = $this->permanentLogin;

        if ($permanent) {

            if(!$strtotime) $strtotime = $this->permanentLoginDuration;

            $timestamp = strtotime($strtotime);

            $token = $this->token->generateToken();

            $hashSelector = sha1($uid . $token);
            $hashToken = sha1($token);

            $this->sessions->setCookie('permanent', $hashSelector . ':' . $hashToken, $strtotime);

            $q = $this->pdo->prepare('INSERT INTO users_logged (user_id, selector, token, expires) VALUES (?, ?, ?, ?)');
            $q->execute([$uid, $hashSelector, $hashToken, $timestamp]);
        }
    }

    public function logout()
    {
    }

    public function redirect($url = false)
    {
        exit();
    }

    public function renewPassword()
    {
    }

    public function sendActivationEmail()
    {
    }

    /**
     * @param $email - User email
     * @param $attempts - Max attempts count during time period
     * @param $during - Time period in which count login attempts
     * @param $deny - Login denial time after last unsuccessful login attempt
     * @return bool Return true if there are more attempts than is allowed
     */
    // $email, $ip, 30, '5 minutes', '30 minutes'
    private function attempt($email, $attempts, $during, $deny)
    {
        $this->req->getReqIp();
        return true;
    }

    // Todo: [P]/api/login/, [P]/api/signup/, [G]/api/logout/, [P]/api/renew/, [G]/api/activate/
}