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
     * @param Flash $flash
     * @param WRouter $router
     * @param WTranslation $translation
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
     * @param bool|string $routeName
     * @param bool $referrer
     * @param string $hashtag
     * @throws \Exception
     */
    public function isNotLogged(Request $request, \Closure $next, $routeName = false, $referrer = true, $hashtag = '')
    {
        $uid = $this->auth->isLogged();

        if ($uid) {

            $next($request);

        } else {

            // Err: User is not logged
            $this->flash->addFlashNext('err', $this->translation->_t('auth.msg.user-not-logged'));

            if ($routeName) {
                $this->confLoginRouteName($routeName);
            }

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl()) . $hashtag;
            } else {
                $url = $this->getLoginUrl() . $hashtag;
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * If user is logged redirect him to routeName otherwise run next middleware.
     * @param Request $request
     * @param \Closure $next
     * @param string $routeName
     * @throws \Exception
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
     * @param bool|string $routeName
     * @param bool $referrer
     * @param string $hashtag
     * @throws \Exception
     */
    public function can(Request $request, \Closure $next, $action, $routeName, $referrer = true, $hashtag = '')
    {
        $uid = $this->auth->userCan($action);

        if (!$uid) {

            $next($request);

        } else {

            $this->confLoginRouteName($routeName);

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl()) . $hashtag;
            } else {
                $url = $this->getLoginUrl() . $hashtag;
            }

            $this->auth->redirect($url);
        }
    }

    /**
     * If user can't perform the action redirect him to login page/routeName, otherwise run next middleware.
     * @param Request $request
     * @param \Closure $next
     * @param string $action
     * @param bool|string $routeName
     * @param bool $referrer
     * @param string $hashtag
     * @throws \Exception
     */
    public function cant(Request $request, \Closure $next, $action, $routeName = false, $referrer = true, $hashtag = '')
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

            if ($routeName) {
                $this->confLoginRouteName($routeName);
            }

            if ($referrer) {
                $url = $this->getLoginUrl() . '?ref=' . urlencode($request->getUrl()) . $hashtag;
            } else {
                $url = $this->getLoginUrl() . $hashtag;
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