<?php
namespace Webiik;

class Signup extends BaseLogin
{
    private $translation;
    private $twig;
    private $flash;
    private $sessions;

    public function __construct(
        Translation $translation,
        \Twig_Environment $twig,
        Auth $auth,
        Flash $flash,
        Csrf $csrf,
        Router $router,
        Sessions $sessions,
        $config
    )
    {
        parent::__construct($auth, $csrf, $router, $config);
        $this->translation = $translation;
        $this->twig = $twig;
        $this->flash = $flash;
        $this->sessions = $sessions;
    }

    public function run()
    {
        // Get merged translations
        // We always get all shared translations and translations only for current page,
        // because Skeleton save resources and adds only these data to Translation class
        $translations = $this->translation->_tAll(false);

        // CSRF protection
        $csrf = $this->csrf();
        if (!$csrf) $this->flash->addFlashNow('err', 'Token mismatch.');

        // Process post data if CSRF is ok
        if ($csrf && $_POST) {

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
                ->filter('minLength', ['msg' => 'Too short.', 'length' => 6]);

            $err = $validator->validate();

            // Prepare error messages
            if (isset($err['err'])) {

                $this->flash->addFlashNow('err', 'Correct red marked fields.');

                foreach ($err['err'] as $data => $messages) {
                    $translations['form']['err'][$data] = $messages;
                }
            }

            // If all inputs are correct
            if (!isset($err['err'])) {

                // Try to set user in to database
                $signup = $this->auth->userSet($email, $pswd, 1);

                // Handle sign-up error
                if ($signup['err'] == 1) {
                    $this->flash->addFlashNow('err', 'Unexpected error.');
                }

                if ($signup['err'] == 2) {
                    $this->flash->addFlashNow('err', 'Too many login attempts.');
                    $translations['form']['err']['email'][] = '';
                    $translations['form']['err']['pswd'][] = '';
                }

                if ($signup['err'] == 3) {
                    $this->flash->addFlashNow('err', 'User already exists.');
                    $translations['form']['err']['email'][] = '';
                    $translations['form']['err']['pswd'][] = '';
                }

                if ($signup['err'] == 4) {
                    $this->flash->addFlashNow('err', 'Unexpected error, it is unable to store user in database.');
                }

                if ($signup['err'] == 5) {
                    $this->flash->addFlashNow('err', 'User was forbidden.');
                    $translations['form']['err']['email'][] = '';
                    $translations['form']['err']['pswd'][] = '';
                }

                if ($signup['err'] == 6) {
                    $this->flash->addFlashNow('err', 'Unexpected error, can\'t create activation token.');
                }

                if ($signup['err'] == 7) {
                    $this->flash->addFlashNow('err', 'Unexpected error, it is unable to store user in social database.');
                }

                // Sign-up was successful
                if (!$signup['err']) {

                    if (isset($signup['selector']) && isset($signup['token'])) {

                        // Activation is required

                        // Activation message
                        $translations['msgActivateP1'] = $this->translation->_p('msgActivateP1', ['timeStamp' => $signup['expires']]);
                        $activationMsg = $translations['msgActivateP1'] . '<a href="' . $this->router->getUrlFor('activate-request') . '">' . $translations['msgActivateP2'] . '</a>';
                        $this->flash->addFlashNext('inf', $activationMsg);

                        // Todo: Send activation email

                        // Create indicator that user can resend activation email
                        $this->sessions->addToSession('resend', $signup['uid']);

                        // Redirect user
                        $this->redirect($this->router->getUrlFor('activate'));

                    } else {

                        // Activation is not required

                        // Login the user
                        $this->auth->userLogin($signup['uid']);

                        // Welcome message
                        $this->flash->addFlashNext('inf', 'Welcome! It\'s great to have you here.');

                        // Redirect user
                        $this->redirect($this->router->getUrlFor($this->config['defaultAfterLoginRouteName']), true);

                    }
                }
            }
        }

        // Render template
        echo $this->twig->render('signup.twig', $translations);
    }
}