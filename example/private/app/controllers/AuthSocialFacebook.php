<?php
namespace Webiik;

class AuthSocialFacebook extends AuthBase
{
    private $flash;
    private $token;

    public function __construct(
        Flash $flash,
        Token $token,
        Auth $auth,
        Csrf $csrf,
        Router $router,
        Translation $translation
    )
    {
        parent::__construct($auth, $csrf, $router, $translation);
        $this->flash = $flash;
        $this->token = $token;
    }

    // Todo: Do not hard code texts of flashes and messages, get them translation
    public function run()
    {
        // Initial settings

        // We need obtain email address from successful social login
        // so set default value of email address to false
        $email = false;
        $provider = 'facebook';

        // Get merged translations
        // We always get all shared translations and translations only for current page,
        // because Skeleton save resources and adds only these data to Translation class
        $translations = $this->translation->_tAll(false);

        // Get login URL, we will use this URL later in code
        $loginUrl = $this->router->getUrlFor('login');

        // Prepare additional query string with referrer and permanent login
        $qsArr = [];
        if(isset($_GET['permanent']) && $_GET['permanent']){
            $qsArr['permanent'] = $_GET['permanent'];
        }
        if ($ref = $this->auth->getReferrer()) {
            $qsArr['ref'] = $ref;
        }
        $qs = count($qsArr) > 0 ? '?' . http_build_query($qsArr) : '';

        // Instantiate OAuth2Client
        $http = new Http();
        $oauth = new OAuth2Client($http);

        // Set authorization callback URL
        $oauth->setRedirectUri($this->router->getUrlFor('social-facebook') . $qs);

        // Set API end points
        $oauth->setAuthorizeUrl('https://www.facebook.com/v2.8/dialog/oauth');
        $oauth->setAccessTokenUrl('https://graph.facebook.com/v2.8/oauth/access_token');
        $oauth->setValidateTokenUrl('https://graph.facebook.com/debug_token');

        // Set API credentials
        $oauth->setClientId('1789792224627518');
        $oauth->setClientSecret('04f626a495ae205185c7271c3d6a7d9a');

        // Make API calls

        // Build log in URL with specific scope and response type
        $apiLoginUrl = $oauth->getLoginUrl(
            [
                'email',
            ],
            'code'
        );

        // Redirect user to login URL
        // Disable current domain check, because we redirect to outside
        if (!isset($_GET['code']) || empty($_GET['code'])) {
            $this->auth->redirect($apiLoginUrl, false);
        }

        // Try to get Access token
        $data = $oauth->getAccessTokenByCode($_GET['code'], 'GET');
        if (!isset($data['access_token']) || empty($data['access_token'])) {
            // Err
            $this->flash->addFlashNext('err', 'Can\'t obtain access token.');
            $this->auth->redirect($loginUrl);
        }

        // Try to get token info
        $info = $oauth->getTokenInfo($data['access_token'], $data['access_token'], 'GET');
        if (!isset($info['data']['user_id']) || empty($info['data']['user_id'])) {
            // Err
            $this->flash->addFlashNext('err', 'Can\'t obtain user id.');
            $this->auth->redirect($loginUrl);
        }

        // Access protected resources
        $query = [
            'access_token' => $data['access_token'],
            'fields' => 'id,name,email',
        ];
        $apiLoginUrl = 'https://graph.facebook.com/v2.8/' . $info['data']['user_id'] . '?' . http_build_query($query);
        $res = $http->get($apiLoginUrl);

        // Try to obtain email address from response
        if (!$res['err'] && isset($res['body'])) {
            $body = json_decode($res['body'], true);
            if (isset($body['email'])) {
                $email = $body['email'];
            }
        }
        if (!$email) {
            // Err
            $this->flash->addFlashNext('err', 'Can\'t obtain user email.');
            $this->auth->redirect($loginUrl);
        }

        // Try to login the user
        $resArr = $this->socialLogin($email, $provider, isset($qsArr['permanent']) ? true : false);

        // Add flash messages if there are some
        if (isset($resArr['msg'])) {
            foreach ($resArr['msg'] as $type => $messages) {
                foreach ($messages as $message) {
                    $this->flash->addFlashNext($type, $message);
                }
            }
        }

        // If user is successfully logged in
        if (isset($resArr['redirectUrl'])) {

            if($resArr['redirectUrl']){

                // If we obtained login redirect URL from referrer or on-page login
                $redirectUrl = $resArr['redirectUrl'];

            } else {

                // Set default login redirect URL
                $redirectUrl = $this->router->getUrlFor('account');
            }

            $this->auth->redirect($redirectUrl);
        }

        // Login failed
        $qs = isset($qsArr['ref']) ? '?' . http_build_query($qsArr['ref']) : '';
        $this->auth->redirect($loginUrl . $qs);
    }
}