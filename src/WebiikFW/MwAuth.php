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
class MwAuth
{
    private $auth;
    private $flash;
    private $router;
    private $translation;

    // Login route name
    private $loginRouteName;

    /**
     * AuthMw constructor.
     * @param AuthExtended $auth
     * @param Router $router
     */
    public function __construct(AuthExtended $auth, Flash $flash, WRouter $router, WTranslation $translation)
    {
        $this->auth = $auth;
        $this->flash = $flash;
        $this->router = $router;
        $this->translation = $translation;
    }

    /**
     * If user isn't logged redirect him to login page/routeName, otherwise run next middleware.
     * @param Request $request
     * @param \Closure $next
     * @param string|bool $routeName
     * @param bool $referrer - Adds ref param to login URL, useful for redirection
     */
    public function isNotLogged(Request $request, \Closure $next, $routeName = false, $referrer = true)
    {
        $uid = $this->auth->isLogged();

        if ($uid) {

            $next($request);

        } else {

            // Err: User is not logged
            $this->flash->addFlashNext('err', $this->translation->_t('auth.msg.user-not-logged'));

            if($routeName) {
                $this->confLoginRouteName($routeName);
            }

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl());
            } else {
                $url = $this->getLoginUrl();
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * If user is logged redirect him to routeName otherwise run next middleware.
     * @param Request $request
     * @param \Closure $next
     * @param string $routeName
     */
    public function isLogged(Request $request, \Closure $next, $routeName)
    {
        $uid = $this->auth->isLogged();

        if ($uid) {
            $this->auth->redirect($this->router->getUrlFor($routeName));
        } else {
            $next($request);
        }
    }

    /**
     * If user can perform the action redirect him to routeName, otherwise run next middleware.
     * @param Request $request
     * @param \Closure $next
     * @param string $action
     * @param string|bool $routeName
     * @param bool $referrer
     */
    public function can(Request $request, \Closure $next, $action, $routeName, $referrer = true)
    {
        $uid = $this->auth->userCan($action);

        if (!$uid) {

            $next($request);

        } else {

            $this->confLoginRouteName($routeName);

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl());
            } else {
                $url = $this->getLoginUrl();
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * If user can't perform the action redirect him to login page/routeName, otherwise run next middleware.
     * @param Request $request
     * @param \Closure $next
     * @param string $action
     * @param string|bool $routeName
     * @param bool $referrer
     */
    public function cant(Request $request, \Closure $next, $action, $routeName = false, $referrer = true)
    {
        $uid = $this->auth->userCan($action);

        if ($uid) {

            $next($request);

        } else {

            $uid = $this->auth->isLogged();

            if ($uid) {
                // Err: User doesn't have sufficient permissions to view this site
                $msg = $this->translation->_t('auth.msg.user-has-no-permissions');
            } else {
                // Err: User is not logged
                $msg = $this->translation->_t('auth.msg.user-not-logged');
            }

            $this->flash->getFlashes(); // clear flashes
            $this->flash->addFlashNext('err', $msg);

            if($routeName) {
                $this->confLoginRouteName($routeName);
            }

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl());
            } else {
                $url = $this->getLoginUrl();
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * @param string $string
     */
    private function confLoginRouteName($string)
    {
        $this->loginRouteName = $string;
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
        if (!$this->loginRouteName) {
            throw new \Exception('Login route name is not set.');
        }

        $url = $this->router->getUrlFor($this->loginRouteName);

        if (!$url) {
            throw new \Exception('Login route name does not exist.');
        }

        return $url;
    }
}