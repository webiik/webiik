<?php
namespace Webiik;

class AuthBase
{
    protected $auth;
    protected $csrf;
    protected $router;
    protected $translation;

    public function __construct(
        Auth $auth,
        Csrf $csrf,
        Router $router,
        Translation $translation
    )
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->router = $router;
        $this->translation = $translation;
    }

    /**
     * Basic sign-up requires email and password. This method searches these values in $_POST.
     * It validates format of these values and it tries to sign up the user using Auth->userSet() method.
     * If everything is ok and sign-up doesn't require activation it'll log the user in.
     *
     * These method returns array:
     * If everything is ok: ['redirectUrl' => false|string, 'msg' => arr, 'form' => arr, 'authErr' = false]
     * If something goes wrong it returns array without 'redirectUrl'.
     * If sign-up requires activation it adds 'withActivation' => true.
     *
     * Explanation of array values:
     * 'redirectUrl' - look at getRedirectUrl() for more info
     *
     * 'form['data']'
     * Array of formatted (not sanitized!) form data.
     *
     * See handleUserSetErrors() for description of the rest of array values.
     *
     * @return array
     */
    protected function signup()
    {
        $resArr = [];

        // Format data
        $email = isset($_POST['email']) ? mb_strtolower(trim($_POST['email'])) : '';
        $pswd = isset($_POST['pswd']) ? str_replace(' ', '', trim($_POST['pswd'])) : '';

        // Add formatted data to response
        $resArr['form']['data']['email'] = $email;
        $resArr['form']['data']['pswd'] = $pswd;

        // CSRF protection
        $csrf = $this->csrf();
        if (!$csrf) {
            // Err: Token mismatch
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.csrf-form');
        }

        if ($csrf && $_POST) {

            // Validate data
            $validator = new Validator();

            $validator->addData('email', $email)
                ->filter('required', ['msg' => $this->translation->_t('auth.msg.entry-required')])
                ->filter('email', ['msg' => $this->translation->_t('auth.msg.entry-invalid')])
                ->filter('maxLength', ['msg' => $this->translation->_t('auth.msg.entry-long'), 'length' => 60]);

            $validator->addData('pswd', $pswd)
                ->filter('required', ['msg' => $this->translation->_t('auth.msg.entry-required')])
                ->filter('minLength', ['msg' => $this->translation->_t('auth.msg.entry-short'), 'length' => 6]);

            $err = $validator->validate();

            // Prepare error messages
            if (isset($err['err'])) {

                $resArr['msg']['err'][] = $this->translation->_t('auth.msg.correct-red-field');

                foreach ($err['err'] as $formFieldName => $messages) {
                    $resArr['form']['msg']['err'][$formFieldName] = $messages;
                }
            }

            if (!isset($err['err'])) {

                // All inputs are correct...

                $userSet = $this->auth->userSet($email, $pswd, 1);

                // Process userSet() errors
                $resArr = array_merge_recursive($this->handleUserSetErrors($userSet), $resArr);

                if (!$userSet['err'] && $userSet['uid'] && !isset($resArr['withActivation'])) {

                    // User is valid and sign-up doesn't require activation

                    // Log the user in
                    $this->auth->userLogin($userSet['uid']);

                    // Welcome message
                    $resArr['msg']['ok'][] = $this->translation->_t('auth.msg.welcome-first');

                    // Redirect user to this URL
                    $resArr['redirectUrl'] = $this->getRedirectUrl(false);

                }
            }
        }

        return $resArr;
    }

    /**
     * Basic log-in requires email and password. This method searches these values in $_POST.
     * It validates format of these values and it tries to log in the user using Auth->userGet() method.
     * If everything is ok it'll log the user in.
     *
     * These method returns array:
     * If everything is ok: ['redirectUrl' => false|string, 'msg' => arr, 'form' => arr]
     * If something goes wrong it returns array without 'redirectUrl'.
     *
     * Explanation of array values:
     * 'redirectUrl' - look at getRedirectUrl() for more info
     *
     * 'form['data']'
     * Array of formatted (not sanitized!) form data.
     *
     * See handleUserGetErrors() for description of the rest of array values.
     *
     * @param bool $onPageLogin
     * @return array
     */
    protected function login($onPageLogin = false)
    {
        $resArr = [];

        // Format form data
        $email = isset($_POST['email']) ? mb_strtolower(trim($_POST['email'])) : '';
        $pswd = isset($_POST['pswd']) ? str_replace(' ', '', trim($_POST['pswd'])) : '';
        $permanent = isset($_POST['permanent']) ? true : false;

        // Add formatted data to response
        $resArr['form']['data']['email'] = $email;
        $resArr['form']['data']['pswd'] = $pswd;
        $resArr['form']['data']['permanent'] = $permanent;

        // CSRF protection
        $csrf = $this->csrf();
        if (!$csrf) {
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.csrf-form');
        }

        if ($csrf && $_POST) {

            // Validate data
            $validator = new Validator();

            $validator->addData('email', $email)
                ->filter('required', ['msg' => $this->translation->_t('auth.msg.entry-required')])
                ->filter('email', ['msg' => $this->translation->_t('auth.msg.entry-invalid')]);

            $validator->addData('pswd', $pswd)
                ->filter('required', ['msg' => $this->translation->_t('auth.msg.entry-required')]);

            $err = $validator->validate();

            // Prepare error messages
            if (isset($err['err'])) {

                $resArr['msg']['err'][] = $this->translation->_t('auth.msg.correct-red-field');

                foreach ($err['err'] as $formFieldName => $messages) {
                    $resArr['form']['msg']['err'][$formFieldName] = $messages;
                }
            }

            if (!isset($err['err'])) {

                // All inputs are correct...

                // Try to get user from database
                $userGet = $this->auth->userGet($email, $pswd);

                // Process userGet() errors
                $resArr = array_merge_recursive($this->handleUserGetErrors($userGet), $resArr);

                if (!$userGet['err'] && $userGet['uid']) {

                    // User is valid...

                    // Log the user in
                    $this->auth->userLogin($userGet['uid'], $permanent);

                    // Welcome message
                    $resArr['msg']['ok'][] = $this->translation->_t('auth.msg.welcome-again');

                    // Redirect user to this URL
                    $resArr['redirectUrl'] = $this->getRedirectUrl($onPageLogin);
                }
            }
        }

        return $resArr;
    }

    /**
     * Read socialLogin() comment for more info.
     * @param $email
     * @param $provider - Eg. 'facebook', 'google'
     * @param bool $permanent
     * @return array
     */
    private function socialSignup($email, $provider, $permanent = false)
    {
        $resArr = [];

        // Try to set user in database
        $userSet = $this->auth->userSet($email, false, 1, $provider);

        // Process userSet() errors
        $resArr = array_merge_recursive($this->handleUserSetErrors($userSet), $resArr);

        if (!$userSet['err'] && $userSet['uid'] && !isset($userSet['tokens'])) {

            // User is valid and sign-up doesn't require activation

            // Login the user
            $this->auth->userLogin($userSet['uid'], $permanent);

            // Welcome message
            $resArr['msg']['ok'][] = $this->translation->_t('auth.msg.welcome-first');

            // Redirect user to this URL
            $resArr['redirectUrl'] = $this->getRedirectUrl(false);

        }

        return $resArr;
    }

    /**
     * Social log-in/signup requires email and provider. This method tries to log in the user
     * using Auth->userGet() method. If user doesn't exist it signs the user up. If everything
     * is ok it'll log the user in.
     *
     * These method returns array:
     * If everything is ok: ['redirectUrl' => false|string, 'msg' => arr, 'form' => arr]
     * If something goes wrong it returns array without 'redirectUrl'.
     *
     * Look at login() method for details about array values.
     *
     * @param $email
     * @param $provider - Eg. 'facebook', 'google'
     * @param bool $permanent
     * @return array
     */
    protected function socialLogin($email, $provider, $permanent = false)
    {
        $resArr = [];

        // Try to get user from database
        $userGet = $this->auth->userGet($email, false, $provider);

        // Process userGet() errors
        $resArr = array_merge_recursive($this->handleUserGetErrors($userGet), $resArr);

        if ($userGet['err'] == 6) {

            // User does not exist, sign up the user
            $resArr = $this->socialSignup($email, $provider, $permanent);

        } else if (!$userGet['err'] && $userGet['uid']) {

            // User is valid...

            // Log the user in
            $this->auth->userLogin($userGet['uid'], $permanent);

            // Welcome message
            $resArr['msg']['ok'][] = $this->translation->_t('auth.msg.welcome-again');

            // Redirect user to this URL
            $resArr['redirectUrl'] = $this->getRedirectUrl(false);
        }

        return $resArr;
    }

    /**
     * Primary it tries to get redirect URL from $_POST['ref'] or $_GET['ref'].
     * If $onPageLogin is true and there is no referrer it will return current URL.
     * In other cases it returns false.
     *
     * @param $onPageLogin
     * @return bool|string
     */
    protected function getRedirectUrl($onPageLogin)
    {
        $redirectUrl = false;

        if ($ref = $this->auth->getReferrer()) {
            $redirectUrl = $ref;
        }

        if (!$ref && $onPageLogin) {
            $redirectUrl = $this->router->getUrlFor($this->router->routeInfo['name']);
        }

        return $redirectUrl;
    }

    /**
     * Set CSRF token and if $_POST is not empty, validate token
     * @return bool
     */
    protected function csrf()
    {
        $err = false;

        if ($_POST) {

            if (!isset($_POST[$this->csrf->getTokenName()])
                || !$this->csrf->validateToken($_POST[$this->csrf->getTokenName()])
            ) {
                $err = true;
            }
        }

        $this->csrf->setToken();

        return !$err;
    }

    /**
     * Process response from auth->userSet() method and prepare formatted array which can contain:
     * ['msg' => arr, 'form' => arr, 'authErr' => int, 'withActivation' => bool]
     *
     * Explanation of array values:
     * 'withActivation'
     * It is set only when user account needs activation and it contains activation URL.
     * It's only up to you how you will manage inactive users.
     *
     * 'msg'
     * Array of flash messages
     *
     * 'form['msg']'
     * Array of error messages for individual form fields.
     *
     * 'authErr'
     * Numeric representation of auth->userGet() error
     *
     * @param $userSet
     * @return array
     */
    private function handleUserSetErrors($userSet)
    {
        $resArr = [];

        // Handle sign-up errors
        if ($userSet['err'] == 1) {
            // Err: Unexpected
            $resArr['msg']['err'][] = $this->translation->_p('auth.msg.unexpected-err', ['operation' => 's', 'errNum' => $resArr['authErr']]);
        }

        if ($userSet['err'] == 2) {
            // Err: To many attempts
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.too-many-attempts');
        }

        if ($userSet['err'] == 3) {
            // Err: User already exists
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.user-already-exists');
            $resArr['form']['msg']['err']['email'][] = '';
            $resArr['form']['msg']['err']['pswd'][] = '';
        }

        if ($userSet['err'] == 4) {
            // Err: Unexpected error, unable to store user in database
            $resArr['msg']['err'][] = $this->translation->_p('auth.msg.unexpected-err', ['operation' => 's', 'errNum' => $resArr['authErr']]);
        }

        if ($userSet['err'] == 5) {
            // Err: Unexpected error, can't generate activation token
            $resArr['msg']['err'][] = $this->translation->_p('auth.msg.unexpected-err', ['operation' => 's', 'errNum' => $resArr['authErr']]);
        }

        if ($userSet['err'] == 6) {
            // Err: Unexpected error, unable to store user in social database
            $resArr['msg']['err'][] = $this->translation->_p('auth.msg.unexpected-err', ['operation' => 's', 'errNum' => $resArr['authErr']]);
        }

        if ($userSet['err'] == 7) {
            // Err: User is banned
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.user-banned');
        }

        if (!$userSet['err'] && isset($userSet['tokens'])) {

            // Sign up requires activation...

            // Prepare message with send activation link
            $rKey = $userSet['tokens']['re-activation']['selector'] . '.' . $userSet['tokens']['re-activation']['token'];
            $rUrl = $this->router->getUrlFor('activation-send') . '?key=' . $rKey;
            $resArr['msg']['inf'][] = $this->translation->_t('auth.msg.user-activate-1') . ' <a href="' . $rUrl . '">' . $this->translation->_t('auth.msg.user-activate-2') . '</a>';

            // Prepare activation confirmation link
            $aKey = $userSet['tokens']['activation']['selector'] . '.' . $userSet['tokens']['activation']['token'];
            $resArr['withActivation'] = $this->router->getUrlFor('activation-confirm') . '?key=' . $aKey;

        }

        $resArr['authErr'] = $userSet['err'];

        return $resArr;
    }

    /**
     * Process response from auth->userSet() method and prepare formatted array which can contain:
     * ['msg' => arr, 'form' => arr, 'authErr' => int]
     *
     * Explanation of array values:
     * 'msg'
     * Array of flash messages
     *
     * 'form['msg']'
     * Array of error messages for individual form fields.
     *
     * 'authErr'
     * Numeric representation of auth->userGet() error
     *
     * @param $userGet
     * @return array
     */
    private function handleUserGetErrors($userGet)
    {
        $resArr = [];

        if ($userGet['err'] == 1) {
            // Err: Unexpected error
            $resArr['msg']['err'][] = $this->translation->_p('auth.msg.unexpected-err', ['operation' => 'l', 'errNum' => $resArr['authErr']]);
        }

        if ($userGet['err'] == 2) {
            // Err: Too many login attempts
            $resArr['msg']['err'][] = 'Too many login attempts.';
        }

        if ($userGet['err'] == 3) {
            // Err: Can't generate token
            $resArr['msg']['err'][] = 'Can\'t generate token.';
        }

        if ($userGet['err'] == 4) {
            // Err: Account is not activated
            $rKey = $userGet['tokens']['re-activation']['selector'] . '.' . $userGet['tokens']['re-activation']['token'];
            $rUrl = $this->router->getUrlFor('activation-send') . '?key=' . $rKey;
            $rMsg = ' <a href="' . $rUrl . '">' . $this->translation->_t('auth.msg.activate-2') . '</a>';
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.activate-1') . $rMsg;
        }

        if ($userGet['err'] == 5) {
            // Err: Unexpected error, more users exist
            $resArr['msg']['err'][] = $this->translation->_p('auth.msg.unexpected-err', ['operation' => 'l', 'errNum' => $resArr['authErr']]);
        }

        if ($userGet['err'] == 6) {
            // Err: User does not exist
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.user-does-not-exist-1') . ' <a href="' . $this->router->getUrlFor('signup') . '">' . $this->translation->_t('auth.msg.user-does-not-exist-2') . '</a>';
            $resArr['form']['msg']['err']['email'][] = '';
            $resArr['form']['msg']['err']['pswd'][] = '';
        }

        if ($userGet['err'] == 7) {
            // Err: User is banned
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.user-banned');
        }

        if ($userGet['err'] == 8) {
            // Err: Password is not set

            // Concatenate providers message
            $providers = '';
            $providersCount = count($userGet['providers']);
            $i = 0;
            foreach ($userGet['providers'] as $provider) {
                $i++;
                if ($providersCount > 1 && $i == $providersCount) {
                    $or = ' ' . $this->translation->_t('auth.msg.pswd-not-set-2') . ' ';
                    $providers .= $or . $provider;
                } elseif ($providersCount > 1) {
                    $providers .= $provider . ',';
                } else {
                    $providers .= $provider;
                }
            }
            $resArr['form']['msg']['err']['pswd'][] = $this->translation->_p('auth.msg.pswd-not-set-1', ['providers' => $providers]);
        }

        if ($userGet['err'] == 9) {
            // Err: Incorrect password
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.pswd-incorrect');
            $resArr['form']['msg']['err']['pswd'][] = '';
        }

        if ($userGet['err'] == 10) {
            // Err: Social is not paired with main account
            $rKey = $userGet['tokens']['re-pairing']['selector'] . '.' . $userGet['tokens']['re-pairing']['token'];
            $provider = $userGet['tokens']['re-pairing']['provider'];
            $rUrl = $this->router->getUrlFor('social-pairing-send') . '?key=' . $rKey . '&provider=' . $provider;
            $rMsg = ' <a href="' . $rUrl . '">' . $this->translation->_t('auth.msg.social-pair-2') . '</a>';
            $resArr['msg']['err'][] = $this->translation->_t('auth.msg.social-pair-1') . $rMsg;
        }

        $resArr['authErr'] = $userGet['err'];

        return $resArr;
    }
}