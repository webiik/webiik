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

    public function __construct($routeInfo, Sessions $sessions, Router $router, Auth $auth, Flash $flash, Csrf $csrf)
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
        if (!$_POST) {
            $this->csrf->setToken();
        }

        if ($_POST) {
            if ($this->csrf->validateToken($_POST[$this->csrf->getTokenName()])) {
                $this->csrf->setToken();
            } else {
                $this->flash->addFlashNow('err', 'Token mismatch.');
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
                    $this->flash->addFlashNow('err', 'Too many login attempts.');
                }

                if ($user == -2) {
                    $this->flash->addFlashNow('err', 'User does not exist.');
                }

                if ($user == -1) {
                    $this->flash->addFlashNow('err', 'User account expired.');
                }

                if ($user == 0) {
                    $this->flash->addFlashNow('err', 'Invalid password.');
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


        $http = new Http();
        $token = new Token();

        $oauth = new OAuth2Client($http);

        // Your callback URL after authorization
        $oauth->setRedirectUri('https://localhost/skeletons/webiik/example/login/');

        // API end points
        $oauth->setAuthorizeUrl('https://www.facebook.com/v2.8/dialog/oauth');
        $oauth->setAccessTokenUrl('https://graph.facebook.com/v2.8/oauth/access_token');
        $oauth->setValidateTokenUrl('https://graph.facebook.com/debug_token');

        // API credentials
        $oauth->setClientId('1789792224627518');
        $oauth->setClientSecret('04f626a495ae205185c7271c3d6a7d9a');

        // Make API calls

        // Log in a user
        $url = $oauth->getLoginUrl(
            [
                'email',
            ],
            'code'
        );
        echo '<a href="' . $url . '">FB login</a>';

        // Get Access token
        if (isset($_GET['code'])) {

            $data = $oauth->getAccessTokenByCode($_GET['code'], 'GET');
            print_r($data);

            if (isset($data['access_token'])) {
                $info = $oauth->getTokenInfo($data['access_token'], $data['access_token'], 'GET');
                print_r($info);
            }
        }

        exit;

//        $oauth = new OAuth1Client($http, $token);
//
//        // Your callback URL after authorization
//        $oauth->setCallbackUrl('https://localhost/skeletons/webiik/example/login/');
//
//        // API end points
//        $oauth->setReqestTokenUrl('https://api.twitter.com/oauth/request_token');
//        $oauth->setAuthorizeUrl('https://api.twitter.com/oauth/authenticate');
//        $oauth->setAccessTokenUrl('https://api.twitter.com/oauth/access_token');
//
//        // API credentials
//        $oauth->setConsumerSecret('rZcebQTj3S01dnVPNeYXwctmxmhvZfG6WdSN9KcCoLzCGrB1g0');
//        $oauth->setConsumerKey('YgMxXP37WfVhJT6t1iC9f5PkB');
//
//        // Make API calls
//
//        // Log in a user
//        if (!isset($_GET['oauth_verifier'])) {
//            $requestTokenData = $oauth->getRequestTokenData();
//            if (isset($requestTokenData['oauth_token'])) {
//                $oauth->redirectToLoginUrl($requestTokenData['oauth_token']);
//            }
//        }
//
//        $accessToken = $oauth->getAccessTokenData();
//
//        print_r($accessToken);
//
//        exit;

//        // GOOGLE
//
//        // API SETTINGS
//        $clientId = '661558313642-k9q0kpsqfo3kiopinufjibmoo0dja7q5.apps.googleusercontent.com';
//        $clientSecret = 'KB9aZO0P41PxfvJdLFc8ym68';
//        $callbackUrl = 'https://localhost/skeletons/webiik/example/login';
//
//        // STEP 1 - Redirect user for authorization
//        $scope = [
//            'https://www.googleapis.com/auth/userinfo.email',
//            'https://www.googleapis.com/auth/userinfo.profile',
//        ];
//
//        $data = [
//            'client_id' => $clientId,
//            'scope' => implode(' ', $scope),
//            'redirect_uri' => $callbackUrl,
//            'response_type' => 'code',
//        ];
//
//        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($data);
//        echo '<a href="' . $url . '">Google login</a>';
//
//        // STEP 2 - Getting access token
//        if (isset($_GET['code'])) {
//
//            $data = [
//                'client_id' => $clientId,
//                'client_secret' => $clientSecret,
//                'redirect_uri' => $callbackUrl,
//                'code' => $_GET['code'],
//                'grant_type' => 'authorization_code'
//            ];
//
//            $url = 'https://www.googleapis.com/oauth2/v4/token';
//
//            $res = $http->post($url, [], $data);
//
//            $data = json_decode($res['body'], true);
//
//            $accessToken = $data['access_token'];
//            $tokenType = $data['token_type'];
//            $expiresIn = $data['expires_in'];
//            $idToken = $data['id_token'];
//
//            print_r($res);
//
//            // STEP 3 - Get token info
//            $data = [
//                'input_token' => $accessToken,
//                'access_token' => $accessToken,
//            ];
//            $url = 'https://www.googleapis.com/oauth2/v2/tokeninfo';
//
//            $res = $http->post($url, [], $data);
//
//            print_r($res);
//
//            // STEP 4 - Access protected resources (obtain user info)
//            $data = [
//                'access_token' => $accessToken,
//                'fields' => 'id,name,email',
//            ];
//
//            $url = 'https://www.googleapis.com/oauth2/v2/userinfo?' . http_build_query($data);;
//            $res = $http->get($url);
//
//            print_r($res);
//
//        }
//
//        // FACEBOOK
//
//        // API SETTINGS
//        $appId = '1789792224627518';
//        $appSecret = '04f626a495ae205185c7271c3d6a7d9a';
//        $callbackUrl = 'https://localhost/skeletons/webiik/example/login/';
//
//        // STEP 1 - Redirect user for authorization
//        $data = [
//            'client_id' => $appId,
//            'redirect_uri' => $callbackUrl,
//            'auth_type' => 'rerequest',
//            'scope' => 'email',
//        ];
//        $url = 'https://www.facebook.com/v2.8/dialog/oauth?' . http_build_query($data);
//        echo '<a href="' . $url . '">FB login</a>';
//
//        // STEP 2 - Getting access token
//        if (isset($_GET['code'])) {
//
//            $data = [
//                'client_id' => $appId,
//                'redirect_uri' => $callbackUrl,
//                'client_secret' => $appSecret,
//                'code' => $_GET['code'],
//            ];
//
//            $url = 'https://graph.facebook.com/v2.8/oauth/access_token?' . http_build_query($data);
//
//            $res = $http->get($url);
//
//            $data = json_decode($res['body'], true);
//
//            $accessToken = $data['access_token'];
//            $tokenType = $data['token_type'];
//            $expiresIn = $data['expires_in'];
//            $authType = $data['auth_type'];
//
//            print_r($res);
//
//            // STEP 3 - Get token info and FB user ID (Inspecting Access Tokens)
//            $data = [
//                'input_token' => $accessToken,
//                'access_token' => $appId . '|' . $appSecret,
//            ];
//            $url = 'https://graph.facebook.com/debug_token?' . http_build_query($data);
//
//            $res = $http->get($url);
//
//            $data = json_decode($res['body'], true);
//
//            print_r($res);
//
//            $userId = $data['data']['user_id'];
//
//            // STEP 4 - Access protected resources (obtain user info)
//            $data = [
//                'access_token' => $accessToken,
//                'fields' => 'id,name,email',
//            ];
//
//            $url = 'https://graph.facebook.com/v2.8/' . $userId . '?' . http_build_query($data);;
//            $res = $http->get($url);
//
//            print_r($res);
//
//        }
//
//        // TWITTER
//
//        // API SETTINGS
//        $consumerSecret = 'rZcebQTj3S01dnVPNeYXwctmxmhvZfG6WdSN9KcCoLzCGrB1g0';
//        $consumerKey = 'YgMxXP37WfVhJT6t1iC9f5PkB';
//        $oauth_signature_method = 'HMAC-SHA1';
//
//        // My Twitter Auth
//        if (!isset($_GET['oauth_verifier'])) {
//
//            // STEP 1 - TWITTER OBTAINING A REQUEST TOKEN
//            $callbackUrl = 'https://localhost/skeletons/webiik/example/login/';
//            $url = 'https://api.twitter.com/oauth/request_token';
//
//            // Data we will send
//            $data = [
//                'oauth_callback' => $callbackUrl,
//                'oauth_consumer_key' => $consumerKey,
//                'oauth_signature_method' => $oauth_signature_method,
//                'oauth_timestamp' => time(),
//                'oauth_nonce' => $token->generate(3),
//                'oauth_version' => '1.0',
//            ];
//
//            // Sort data alphabetically, because Twitter requires that
//            ksort($data);
//
//            // Generate signature and add it to data array
//            $signData = 'POST&' . urlencode($url) . '&' . urlencode(http_build_query($data));
//            $secret = '';
//            $signKey = urlencode($consumerSecret) . '&' . urlencode($secret);
//            $data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signData, $signKey, true));
//
//            // Prepare http headers from data
//            $httpHeaders = [];
//            foreach ($data as $key => $value) {
//                $httpHeaders[] = urlencode($key) . '="' . urlencode($value) . '"';
//            }
//
//            // Add OAuth header with all data
//            $httpHeaders = 'Authorization: OAuth ' . implode(', ', $httpHeaders);
//
//            // Send post request to Twitter API with http headers and data
//            $res = $http->post($url, ['httpHeaders' => [$httpHeaders]], []);
//
//            // If we got some error, show error message and stop
//            if ($res['status'] != 200) {
//                echo $res['err'];
//                exit;
//            }
//
//            // Prepare data for step 2 and 3 from Twitter's response
//            parse_str($res['body'], $res);
////            $oauth_callback_confirmed = $res['oauth_callback_confirmed'];
//            $oauth_request_token = $res['oauth_token'];
//
//            // Store oauth_token_secret into session, we will need it in step 3
////            $this->sessions->setToSession('oauth_token_secret', $res['oauth_token_secret']);
////            $this->sessions->setToSession('oauth_token', $oauth_request_token);
//
//            // STEP 2 - REDIRECTING THE USER TO TWITTER LOGIN
//            header('HTTP/1.1 302 Found');
//            header('Location: https://api.twitter.com/oauth/authenticate?oauth_token=' . urlencode($oauth_request_token));
//        }
//
//        // STEP 3 - CONVERTING THE REQUEST TOKEN TO AN ACCESS TOKEN
//        $url = 'https://api.twitter.com/oauth/access_token';
//        $oauth_token = $_GET['oauth_token'];
//        $oauth_verifier = $_GET['oauth_verifier'];
//
//        // Data we will send
//        $data = [
//            'oauth_consumer_key' => $consumerKey,
//            'oauth_nonce' => $token->generate(3),
//            'oauth_signature_method' => $oauth_signature_method,
//            'oauth_timestamp' => time(),
//            'oauth_token' => $oauth_token,
//            'oauth_version' => '1.0',
//        ];
//
//        // Sort data alphabetically, because Twitter requires that
//        ksort($data);
//
//        // Generate signature and add it to data array
//        $signData = 'POST&' . urlencode($url) . '&' . urlencode(http_build_query($data));
//        $secret = '';
//        $signKey = urlencode($consumerSecret) . '&' . urlencode($secret);
//        $data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signData, $signKey, true));
//
//        // Prepare http headers from data
//        $httpHeaders = [];
//        foreach ($data as $key => $value) {
//            $httpHeaders[] = urlencode($key) . '="' . urlencode($value) . '"';
//        }
//
//        // Add OAuth header with all data
//        $httpHeaders = ['Authorization: OAuth ' . implode(', ', $httpHeaders)];
//        $httpHeaders[] = 'Content-Length: ' . strlen('oauth_verifier=' . urlencode($oauth_verifier));
//        $httpHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
//
//        // Add oauth_verifier to POST data
//        $postData = ['oauth_verifier' => $oauth_verifier];
//
//        // Send post request to Twitter API with http headers and data
//        $res = $http->post($url, ['httpHeaders' => $httpHeaders], $postData);
//
//        // If we got some error, show error message and stop
//        if ($res['status'] != 200) {
//            print_r($res);
//            exit;
//        }
//
//        print_r($res);
    }
}