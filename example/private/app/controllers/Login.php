<?php
namespace Webiik;

class Login
{
    private $translation;
    private $twig;

    private $auth;
    private $flash;
    private $csrf;
    private $router;
    private $config;

    public function __construct(
        Translation $translation,
        \Twig_Environment $twig,
        Auth $auth,
        Flash $flash,
        Csrf $csrf,
        Router $router,
        $config
    )
    {
        $this->translation = $translation;
        $this->twig = $twig;
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->flash = $flash;
        $this->router = $router;
        $this->config = $config['authAPI'];
    }

    public function run()
    {
        // Get merged translations
        // We always get all shared translations and translations only for current page,
        // because Skeleton save resources and adds only these data to Translation class
        $translations = $this->translation->_tAll(false);

        // CSRF protection
        if (!$_POST) {
            $this->csrf->setToken();
        }

        if ($_POST) {
            if ($this->csrf->validateToken($_POST[$this->csrf->getTokenName()])) {
                $this->csrf->setToken();
            } else {
                // Todo: Add texts to flashes from Translation
                $this->flash->addFlashNow('err', 'Token mismatch.');
                $formErr = true;
            }
        }

        // Post data
        if ($_POST && !isset($formErr)) {

            // Format data
            $email = mb_strtolower(trim($_POST['email']));
            $pswd = str_replace(' ', '', trim($_POST['pswd']));

            // Prepare form data
            $translations['form']['email'] = $email;
            $translations['form']['pswd'] = $pswd;

            // Validate data
            $validator = new Validator();

            $validator->addData('email', $email)
                ->filter('required', ['msg' => 'Required field.'])
                ->filter('email', ['msg' => 'Invalid email format.']);

            $validator->addData('pswd', $pswd)
                ->filter('required', ['msg' => 'Required field.'])
                ->filter('minLength', ['msg' => 'Too short.', 'length' => 4]);

            $err = $validator->validate();

            // Prepare error messages
            if (isset($err['err'])) {

                $formErr = true;
                $this->flash->addFlashNow('err', 'Correct red marked fields.');

                foreach ($err['err'] as $data => $messages) {
                    $translations['form']['err'][$data] = $messages;
                }
            }

            // Send request to Account API
            if (!isset($formErr)) {

                $http = new Http();

                $options = [
                    'getHeaders' => true,
                    'httpHeaders' => [
                        'X-WEBIIK-SECRET: ' . $this->config['secret'],
                    ],
                ];

                $res = $http->post($this->router->getUrlFor('api-login'), $options, [
                    'email' => $email,
                    'pswd' => $pswd,
                ]);

                $res = json_decode($res['body'], true);

                // Do we have awaited response?
                if (isset($res['status'])) {

                    // Handle successful login
                    if ($res['status'] == 'ok') {

                        // Login the user
                        $this->auth->userLogin($res['user']['id']);

                        // Check if activation is required and if user is activated
                        if (isset($res['user']['status'])) {

                            $translations['user']['status'] = $res['user']['status'];

                            // User is not activated
                            if ($res['user']['status'] == 0) {
                                $this->flash->addFlashNext('inf', 'Activate your account, otherwise will be deleted within 24 hours.');
                            }
                        }

                        // If we have valid referrer
                        // redirect user to that page
                        $referrer = $this->getReferrer();
                        if ($referrer) {
                            $this->auth->redirect($referrer);
                        }

                        // If login is accessed from login page without any referrer
                        // redirect user to defaultAfterLoginRouteName, otherwise
                        // redirect user to current page.
                        $loginUrl = $this->router->getUrlFor($this->config['loginRouteName']);
                        $currentUrl = $this->router->getUrlFor($this->router->routeInfo['name']);

                        if ($loginUrl == $currentUrl) {
                            $redirUrl = $this->router->getUrlFor($this->config['defaultAfterLoginRouteName']);
                        } else {
                            $redirUrl = $currentUrl;
                        }

                        $this->auth->redirect($redirUrl);
                    }

                    // Handle error
                    if ($res['status'] == 'err') {

                        if ($res['err_code'] == -6) {

                            $this->flash->addFlashNow('err', 'Correct red marked fields.');

                            foreach ($res['missing'] as $data) {
                                $translations['form']['err'][$data] = [];
                            }
                        }

                        if ($res['err_code'] == -5) {
                            $this->flash->addFlashNow('err', 'Unauthorized access.');
                        }

                        if ($res['err_code'] == -4) {
                            $this->flash->addFlashNow('err', 'Unknown login error.');
                        }

                        if ($res['err_code'] == -3) {
                            $this->flash->addFlashNow('err', 'Too many login attempts.');
                        }

                        if ($res['err_code'] == -2) {
                            $this->flash->addFlashNow('err', 'User does not exist.');
                            $translations['form']['err']['email'][] = '';
                        }

                        if ($res['err_code'] == -1) {
                            $this->flash->addFlashNow('err', 'User account expired.');
                        }

                        if ($res['err_code'] == 0) {
                            $this->flash->addFlashNow('err', 'Invalid password.');
                            $translations['form']['err']['pswd'][] = '';
                        }
                    }

                } else {

                    // Unknown error
                    $this->flash->addFlashNow('err', 'Unknown API error.');
                }
            }
        }

        // Render page
        echo $this->twig->render('login.twig', $translations);

        if($email){

            // Yes

        } else {

            // No
            $err = 'User does not exist.';
        }
    }

    private function getReferrer()
    {
        $referrer = false;

        if (isset($_POST['ref'])) {
            $referrer = $_POST['ref'];
        } elseif (isset($_GET['ref'])) {
            $referrer = $_GET['ref'];
        }

        return $referrer;
    }
}