<?php
namespace Webiik;

class AuthActivationSend extends AuthBase
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
        $rToken = $this->auth->tokenValidate($key[0], $key[1], 'auth_tokens_re_activation');
        if ($rToken['err']) {
            // Err - invalid re-activation key
            $this->flash->addFlashNext('err', 'Invalid key.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Generate new activation token
        $token = $this->auth->tokenGenerate($rToken['uid'], 'auth_tokens_activation', $rToken['expires']);

        if ($token['err']) {
            // Err - error during generating token
            $this->flash->addFlashNext('err', 'Can\'t generate activation key.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Get user email
        $pdo = $this->connection->connect();
        $q = $pdo->prepare('SELECT email FROM auth_users WHERE id = ?');
        $q->execute([$rToken['uid']]);
        $email = $q->fetchColumn();

        // Todo: Send activation email
        $aKey = $token['selector'] . '.' . $token['token'];
        $aUrl = $this->router->getUrlFor('activation-confirm') . '?key=' . $aKey;
        echo $aUrl;
        exit;

        // Redirect back to login page
        $this->flash->addFlashNext('ok', 'Activation message has been send to ' . htmlspecialchars($email) . '.');
        $this->auth->redirect($this->router->getUrlFor('login'));
    }
}