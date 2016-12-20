<?php
namespace Webiik;

class AuthMw
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Router
     */
    private $router;

    /**
     * Auth configuration
     * @var $config
     */
    private $config = [
        // Login route name
        'loginRouteName' => false,
    ];

    /**
     * AuthMw constructor.
     * @param Auth $auth
     * @param Router $router
     */
    public function __construct(Auth $auth, Router $router)
    {
        $this->auth = $auth;
        $this->router = $router;
    }

    /**
     * @param string $string
     */
    public function confLoginRouteName($string)
    {
        $this->config['loginRouteName'] = $string;
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * If user is logged in, add uid into Request and run next middleware
     * If user is logged out redirect user to 'routeName' page or home page
     * @param Request $request
     * @param \Closure $next
     * @param bool $backRef
     */
    public function redirectUnloggedUser(Request $request, \Closure $next, $backRef = true)
    {
        $uid = $this->auth->isUserLogged();

        if ($uid) {

            $request->set('uid', $uid);
            $next($request);

        } else {

            if ($backRef) {
                $url = $this->getLoginUrl() . '?ref=' . $request->getUrl();
            } else {
                $url = $this->getLoginUrl();
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * If user is logged in redirect user to 'routeName' page or home page
     * If user is logged out run next middleware
     * @param Request $request
     * @param \Closure $next
     * @param bool $routeName
     */
    public function redirectLoggedUser(Request $request, \Closure $next, $routeName = false)
    {
        $uid = $this->auth->isUserLogged();

        if (!$uid) {

            $next($request);

        } else {

            // If there is valid referrer, redirect user to that referrer
            $url = $this->getReferrer();
            $this->auth->redirect($url);

            // If there is no valid referrer, the redirect automatically fails,
            // so redirect the user to routeName page or home page
            $url = false;

            if ($routeName) {
                $url = $request->getRootUrl() . $this->router->getUriFor($routeName);
            }

            if (!$url) {
                $url = $request->getRootUrl() . $this->router->getBasePath();
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * Check if user can perform the action
     * On success add uid to Request and run next middleware
     * On fail redirect to login page
     * @param Request $request
     * @param \Closure $next
     * @param string $action
     * @param bool $backRef
     */
    public function userCan(Request $request, \Closure $next, $action, $backRef = true)
    {
        $uid = $this->auth->userCan($action);

        if ($uid) {

            $request->set('uid', $uid);
            $next($request);

        } else {

            $uid = $this->auth->isUserLogged();

            if ($uid) {
                // User does not have permissions to view this site
                // Redirect user to home page
                $this->auth->redirect($request->getRootUrl() . $this->router->getBasePath());
            }

            if ($backRef) {
                $url = $this->getLoginUrl() . '?ref=' . $request->getUrl();
            } else {
                $url = $this->getLoginUrl();
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * Get login URL
     * On success return login URL
     * On error throw exception
     * @return bool|string
     * @throws \Exception
     */
    private function getLoginUrl()
    {
        if (!$this->config['loginRouteName']) {
            throw new \Exception('Login route name is not set.');
        }

        $url = $this->router->getUrlFor($this->config['loginRouteName']);

        if (!$url) {
            throw new \Exception('Login route name does not exist.');
        }

        return $url;
    }

    /**
     * Return referrer from $_GET or $_POST
     * @return mixed
     */
    private function getReferrer()
    {
        $referrer = false;

        // If we have both, get and post must be same
        if (isset($_GET['ref']) && isset($_POST['ref'])) {
            if ($_GET['ref'] != $_POST['ref']) {
                return false;
            }
        }

        if (isset($_GET['ref'])) {
            $referrer = $_GET['ref'];
        }

        if (isset($_POST['ref'])) {
            $referrer = $_POST['ref'];
        }

        return $referrer;
    }
}