<?php
namespace Webiik;

class AuthActivationConfirm extends AuthBase
{
    private $flash;
    private $connection;

    public function __construct(
        Flash $flash,
        Connection $connection,
        Auth $auth,
        Csrf $csrf,
        Router $router,
        Translation $translation
    )
    {
        parent::__construct($auth, $csrf, $router, $translation);
        $this->flash = $flash;
        $this->connection = $connection;
    }

    public function run()
    {
        // Todo: Do not hard-code flash messages

        // Get token
        if (!isset($_GET['key'])) {
            // Err - key is not set
            $this->flash->addFlashNext('err', 'Key is not set.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Explode key
        $key = explode('.', $_GET['key'], 2);
        if (!isset($key[1])) {
            // Err - invalid key
            $this->flash->addFlashNext('err', 'Invalid key format.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Validate key
        $rToken = $this->auth->tokenValidate($key[0], $key[1], 'auth_tokens_activation');
        if ($rToken['err']) {
            // Err - invalid activation key
            $this->flash->addFlashNext('err', 'Invalid key.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Connect DB
        $pdo = $this->connection->connect();

        // Get user email
        $q = $pdo->prepare('SELECT email FROM auth_users WHERE id = ?');
        $q->execute([$rToken['uid']]);
        $email = $q->fetchColumn();

        // Check if there is no other activated user with same email
        $q = $pdo->prepare('SELECT id FROM auth_users WHERE email = ? AND status = 1 LIMIT 1');
        $q->execute([$email]);
        $r = $q->fetch(\PDO::FETCH_ASSOC);
        if ($r) {
            // Err - There is already activated account with same email address by same user
            if($rToken['uid'] == $r['id']){
                $msg = 'You have already activated your account.';
            } else {
                $msg = 'Account has been already activated by another user. You cannot use this account any more.';
            }
            $this->flash->addFlashNext('err', $msg);
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Activate account
        $q = $pdo->prepare('UPDATE auth_users SET status = 1 WHERE id = ?');
        $q->execute([$rToken['uid']]);

        // Expire other inactive accounts with same email address
        $q = $pdo->prepare('UPDATE auth_users SET status = 2 WHERE id != ? AND email = ?');
        $q->execute([$rToken['uid'], $email]);

        // Redirect back to login page
        $this->flash->addFlashNext('ok', 'Your account has been activated. Just log in.');
        $this->auth->redirect($this->router->getUrlFor('login'));
    }
}