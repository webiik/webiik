<?php
namespace Webiik;

/**
 * Class MwAuth
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class MwAuth
{
    private $auth;
    private $csrf;
    private $router;
    private $translation;
    private $render;
    private $flash;

    /**
     * MwAuth constructor.
     * @param Auth $auth
     * @param Csrf $csrf
     * @param WRouter $router
     * @param WTranslation $translation
     * @param WRender $render
     * @param Flash $flash
     */
    public function __construct(Auth $auth, Csrf $csrf, WRouter $router, WTranslation $translation, WRender $render, Flash $flash)
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

            $next($request);

        } else {

            if (!$_POST) {
                // Err: User is not logged
                $this->flash->addFlashNow('err', $this->translation->_t('auth.msg.user-not-logged'));
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

            $next($request);

        } else {

            if (!$_POST) {

                $uid = $this->auth->isUserLogged();

                if ($uid) {
                    // Err: User doesn't have sufficient permissions to view this site
                    $msg = $this->translation->_t('auth.msg.user-has-no-permissions');
                } else {
                    // Err: User is not logged
                    $msg = $this->translation->_t('auth.msg.user-not-logged');
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