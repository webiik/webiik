<?php
namespace Webiik;

class AuthSocialPairingConfirm extends AuthBase
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

        // Get provider
        if (!isset($_GET['provider']) || !$_GET['provider']) {
            // Err - key is not set
            $this->flash->addFlashNext('err', 'Provider is not set.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }
        $provider = $_GET['provider'];

        // Explode key
        $key = explode('.', $_GET['key'], 2);
        if (!isset($key[1])) {
            // Err - invalid key
            $this->flash->addFlashNext('err', 'Invalid key format.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Validate key
        $tableName = 'auth_tokens_pairing_' . $provider;
        $rToken = $this->auth->tokenValidate($key[0], $key[1], $tableName);
        if ($rToken['err']) {
            // Err - invalid re-pairing key or unsupported provider
            if($rToken['err'] == 2){
                $this->flash->addFlashNext('err', 'Unsupported provider.');
            } else {
                $this->flash->addFlashNext('err', 'Invalid key.');
            }
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Connect DB
        $pdo = $this->connection->connect();

        // Pair main account with current social provider
        $q = $pdo->prepare('INSERT INTO auth_users_social (user_id, provider) VALUES (?, ?)');
        $q->execute([$rToken['uid'], $provider]);

        // Redirect back to login page
        $msgProvider = htmlspecialchars(ucfirst(strtolower($provider)));
        $this->flash->addFlashNext('ok', 'Your main account has been paired with ' . $msgProvider . '. Now you can use ' . $msgProvider . ' to login.');
        $this->auth->redirect($this->router->getUrlFor('login'));
    }
}