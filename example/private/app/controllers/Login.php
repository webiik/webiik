<?php
namespace Webiik;

use Simplon\Twitter\Twitter;
use Simplon\Twitter\TwitterException;

class Login
{
    /**
     * @var Sessions
     */
    private $sessions;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Flash
     */
    private $flash;

    /**
     * @var Csrf
     */
    private $csrf;

    public function __construct(Sessions $sessions, Router $router, Auth $auth, Flash $flash, Csrf $csrf)
    {
        $this->sessions = $sessions;
        $this->router = $router;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->csrf = $csrf;
    }

    private function getReferrer()
    {
        if (isset($_POST['ref'])) {
            $referrer = $_POST['ref'];
        } elseif (isset($_GET['ref'])) {
            $referrer = $_GET['ref'];
        } else {
            $referrer = $this->router->getUrlFor('account');
        }

        return $referrer;
    }

    public function run()
    {
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $pswd = isset($_POST['pswd']) ? $_POST['pswd'] : '';
        $referrer = $this->getReferrer();

        // CSRF protection
        $this->sessions->sessionStart();

        if (!$_POST) {
            $this->csrf->setToken();
        }

        if ($_POST) {
            if ($this->csrf->validateToken($_POST[$this->csrf->getTokenName()])) {
                $this->csrf->setToken();
            } else {
                $this->flash->setFlashNow('err', 'Token mismatch.');
            }
        }

        // Post data
        if ($_POST && count($this->flash->getFlashes('err')) == 0) {

            $user = $this->auth->userGet($_POST['email'], $_POST['pswd']);

            if (is_array($user)) {

                $uid = $user['uid'];
                $this->auth->userLogin($uid);

                if (!$this->auth->redirect($referrer)) {
                    $this->auth->redirect($this->router->getUrlFor('account'));
                }

            } else {

                if ($user == -3) {
                    $this->flash->setFlashNow('err', 'Too many login attempts.');
                }

                if ($user == -2) {
                    $this->flash->setFlashNow('err', 'User does not exist.');
                }

                if ($user == -1) {
                    $this->flash->setFlashNow('err', 'User account expired.');
                }

                if ($user == 0) {
                    $this->flash->setFlashNow('err', 'Invalid password.');
                }

            }
        }

        // Messages
        if (count($this->flash->getFlashes()) > 0) {
            print_r($this->flash->getFlashes());
        }

        echo '<form action="" method="post">';
        echo '<input type="text" name="email" placeholder="email" value="' . $email . '">';
        echo '<input type="password" name="pswd" placeholder="password" value="' . $pswd . '">';
        echo $this->csrf->getHiddenInput();
        echo '<input type="hidden" name="ref" value="' . $referrer . '">';
        echo '<input type="submit" value="login">';
        echo '</form>';

        // Twitter auth test
        $http = new Http();
        $token = new Token();

        // API SETTINGS
        $consumerSecret = 'rZcebQTj3S01dnVPNeYXwctmxmhvZfG6WdSN9KcCoLzCGrB1g0';
        $consumerKey = 'YgMxXP37WfVhJT6t1iC9f5PkB';
        $oauth_signature_method = 'HMAC-SHA1';

        // My Twitter Auth
        if (!isset($_GET['oauth_verifier'])) {

            // STEP 1 - TWITTER OBTAINING A REQUEST TOKEN
            $callbackUrl = 'https://localhost/skeletons/webiik/example/login/';
            $url = 'https://api.twitter.com/oauth/request_token';

            // Data we will send
            $data = [
                'oauth_callback' => $callbackUrl,
                'oauth_consumer_key' => $consumerKey,
                'oauth_signature_method' => $oauth_signature_method,
                'oauth_timestamp' => time(),
                'oauth_nonce' => $token->generate(3),
                'oauth_version' => '1.0',
            ];

            // Sort data alphabetically, because Twitter requires that
            ksort($data);

            // Generate signature and add it to data array
            $signData = 'POST&' . urlencode($url) . '&' . urlencode(http_build_query($data));
            $secret = '';
            $signKey = urlencode($consumerSecret) . '&' . urlencode($secret);
            $data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signData, $signKey, true));

            // Prepare http headers from data
            $httpHeaders = [];
            foreach ($data as $key => $value) {
                $httpHeaders[] = urlencode($key) . '="' . urlencode($value) . '"';
            }

            // Add OAuth header with all data
            $httpHeaders = 'Authorization: OAuth ' . implode(', ', $httpHeaders);

            // Send post request to Twitter API with http headers and data
            $res = $http->post($url, ['httpHeaders' => [$httpHeaders]], []);

            // If we got some error, show error message and stop
            if ($res['status'] != 200) {
                echo $res['err'];
                exit;
            }

            // Prepare data for step 2 and 3 from Twitter's response
            parse_str($res['body'], $res);
//            $oauth_callback_confirmed = $res['oauth_callback_confirmed'];
            $oauth_request_token = $res['oauth_token'];

            // Store oauth_token_secret into session, we will need it in step 3
//            $this->sessions->setToSession('oauth_token_secret', $res['oauth_token_secret']);
//            $this->sessions->setToSession('oauth_token', $oauth_request_token);

            // STEP 2 - REDIRECTING THE USER TO TWITTER LOGIN
            header('HTTP/1.1 302 Found');
            header('Location: https://api.twitter.com/oauth/authenticate?oauth_token=' . urlencode($oauth_request_token));
        }

        // STEP 3 - CONVERTING THE REQUEST TOKEN TO AN ACCESS TOKEN
        $url = 'https://api.twitter.com/oauth/access_token';
        $oauth_token = $_GET['oauth_token'];
        $oauth_verifier = $_GET['oauth_verifier'];

        // Data we will send
        $data = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => $token->generate(3),
            'oauth_signature_method' => $oauth_signature_method,
            'oauth_timestamp' => time(),
            'oauth_token' => $oauth_token,
            'oauth_version' => '1.0',
        ];

        // Sort data alphabetically, because Twitter requires that
        ksort($data);

        // Generate signature and add it to data array
        $signData = 'POST&' . urlencode($url) . '&' . urlencode(http_build_query($data));
        $secret = '';
        $signKey = urlencode($consumerSecret) . '&' . urlencode($secret);
        $data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signData, $signKey, true));

        // Prepare http headers from data
        $httpHeaders = [];
        foreach ($data as $key => $value) {
            $httpHeaders[] = urlencode($key) . '="' . urlencode($value) . '"';
        }

        // Add OAuth header with all data
        $httpHeaders = ['Authorization: OAuth ' . implode(', ', $httpHeaders)];
        $httpHeaders[] = 'Content-Length: ' . strlen('oauth_verifier=' . urlencode($oauth_verifier));
        $httpHeaders[] = 'Content-Type: application/x-www-form-urlencoded';

        // Add oauth_verifier to POST data
        $postData = ['oauth_verifier' => $oauth_verifier];

        // Send post request to Twitter API with http headers and data
        $res = $http->post($url, ['httpHeaders' => $httpHeaders], $postData);

        // If we got some error, show error message and stop
        if ($res['status'] != 200) {
            print_r($res);
            exit;
        }

        print_r($res);
    }
}