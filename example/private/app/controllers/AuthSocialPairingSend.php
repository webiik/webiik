<?php
namespace Webiik;

class AuthSocialPairingSend extends AuthBase
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
        $tableName = 'auth_tokens_re_pairing_' . $provider;
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

        // Generate new re-pairing token
        $tableName = 'auth_tokens_pairing_' . $provider;
        $token = $this->auth->tokenGenerate($rToken['uid'], $tableName, $rToken['expires']);

        if ($token['err']) {
            // Err - error during generating token
            $this->flash->addFlashNext('err', 'Can\'t generate pairing key.');
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // Get user email
        $pdo = $this->connection->connect();
        $q = $pdo->prepare('SELECT email FROM auth_users WHERE id = ?');
        $q->execute([$rToken['uid']]);
        $email = $q->fetchColumn();

        // Todo: Send activation email
        $aKey = $token['selector'] . '.' . $token['token'];
        $aUrl = $this->router->getUrlFor('social-pairing-confirm') . '?key=' . $aKey . '&provider=' . $provider;
        echo $aUrl;
        exit;

        // Redirect back to login page
        $this->flash->addFlashNext('ok', 'Pairing message has been send to ' . htmlspecialchars($email) . '.');
        $this->auth->redirect($this->router->getUrlFor('login'));
    }
}