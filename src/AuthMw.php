<?php
namespace Webiik;

class AuthMw
{
    private $auth;
    private $csrf;
    private $router;
    private $translation;
    private $render;
    private $flash;

    // Todo: Do not hard-code texts of flashes and messages

    /**
     * AuthMw constructor.
     * @param Auth $auth
     * @param Router $router
     */
    public function __construct(Auth $auth, Csrf $csrf, Router $router, Translation $translation, Render $render, Flash $flash)
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->router = $router;
        $this->translation = $translation;
        $this->render = $render;
        $this->flash = $flash;
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * If user is logged, add uid to Request and run next middleware
     * If user isn't logged, show login page
     * @param Request $request
     * @param \Closure $next
     */
    public function isLogged(Request $request, \Closure $next)
    {
        $uid = $this->auth->isUserLogged();

        if ($uid) {

            $request->set('uid', $uid);
            $next($request);

        } else {

            if (!$_POST) {
                $this->flash->addFlashNow('err', 'Log in to view this site.');
            }

            $this->showLoginPage();
        }
    }

    /**
     * Check if user isn't logged in and create logged session if it's necessary
     * If user isn't logged, run next middleware
     * If user is logged, redirect user to $routeName
     * @param Request $request
     * @param \Closure $next
     */
    public function isNotLogged(Request $request, \Closure $next, $routeName)
    {
        $uid = $this->auth->isUserLogged();

        if ($uid) {
            $request->set('uid', $uid);
            $this->auth->redirect($this->router->getUrlFor($routeName));
        } else {
            $next($request);
        }
    }

    /**
     * Check if user can perform the action
     * On success add uid to Request and run next middleware
     * On fail show login page
     * @param Request $request
     * @param \Closure $next
     * @param string $action
     */
    public function can(Request $request, \Closure $next, $action)
    {
        $uid = $this->auth->userCan($action);

        if ($uid) {

            $request->set('uid', $uid);
            $next($request);

        } else {

            if (!$_POST) {

                $uid = $this->auth->isUserLogged();

                if ($uid) {
                    $msg = 'You don\'t have permissions to view this site.';
                } else {
                    $msg = 'Log in to view this site.';
                }

                $this->flash->addFlashNow('err', $msg);
            }

            $this->showLoginPage();
        }
    }

    /**
     * Run login page controller
     */
    private function showLoginPage()
    {
        $loginController = new AuthLogin($this->flash, $this->render, $this->auth, $this->csrf, $this->router, $this->translation);
        $loginController->run(true);
    }
}