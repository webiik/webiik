<?php
namespace Webiik;

/**
 * Class AuthMwRedirect
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class MwAuthRedirect
{
    private $auth;
    private $flash;
    private $router;
    private $translation;
    private $config = [
        // Login route name
        'loginRouteName' => false,
    ];

    /**
     * AuthMw constructor.
     * @param Auth $auth
     * @param Router $router
     */
    public function __construct(Auth $auth, Flash $flash, WRouter $router, WTranslation $translation)
    {
        $this->auth = $auth;
        $this->flash = $flash;
        $this->router = $router;
        $this->translation = $translation;
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
     * If user is logged, add uid to Request and run next middleware
     * If user isn't logged, redirect user to login page
     * @param Request $request
     * @param \Closure $next
     * @param bool $referrer - Adds ref param to login URL, useful for redirection
     */
    public function isLogged(Request $request, \Closure $next, $referrer = true)
    {
        $uid = $this->auth->isUserLogged();

        if ($uid) {

            $next($request);

        } else {

            // Err: User is not logged
            $this->flash->addFlashNext('err', $this->translation->_t('auth.msg.user-not-logged'));

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl());
            } else {
                $url = $this->getLoginUrl();
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * If user isn't logged, run next middleware
     * If user is logged, redirect user to $routeName
     * @param Request $request
     * @param \Closure $next
     * @param bool $routeName
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
     * On fail redirect to login page
     * @param Request $request
     * @param \Closure $next
     * @param string $action
     * @param bool $referrer
     */
    public function can(Request $request, \Closure $next, $action, $referrer = true)
    {
        $uid = $this->auth->userCan($action);

        if ($uid) {

            $next($request);

        } else {

            $uid = $this->auth->isUserLogged();

            if ($uid) {
                // Err: User doesn't have sufficient permissions to view this site
                $msg = $this->translation->_t('auth.msg.user-has-no-permissions');
            } else {
                // Err: User is not logged
                $msg = $this->translation->_t('auth.msg.user-not-logged');
            }

            $this->flash->getFlashes(); // clear flashes
            $this->flash->addFlashNext('err', $msg);

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl());
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
}