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
     * @param Sessions $sessions
     */
    public function __construct(Auth $auth, Router $router, Sessions $sessions)
    {
        $this->auth = $auth;
        $this->router = $router;
        $sessions->sessionStart();
    }

    /**
     * @param string $string
     */
    public function setLoginRouteName($string)
    {
        $this->config['loginRouteName'] = $string;
    }

    /**
     * Check if user is logged in and create logged session if it's necessary
     * On success add uid to Request and run next middleware
     * On fail redirect to login page
     * @param Request $request
     * @param \Closure $next
     * @param bool $routeName
     */
    public function isUserLogged(Request $request, \Closure $next, $routeName = false)
    {
        $uid = $this->auth->isUserLogged();

        if ($uid) {
            $request->set('uid', $uid);
            $next($request);
        } else {
            $this->redirect($request->getRootUrl() . $this->getLoginUri($routeName) . '?ref=' . $request->getUrl());
        }
    }

    /**
     * Check if user can perform the action
     * On success add uid to Request and run next middleware
     * On fail redirect to login page
     * @param Request $request
     * @param \Closure $next
     * @param string $action
     * @param bool $routeName
     */
    public function userCan(Request $request, \Closure $next, $action, $routeName = false)
    {
        $uid = $this->auth->userCan($action);

        if ($uid) {
            $request->set('uid', $uid);
            $next($request);
        } else {
            $this->redirect($request->getRootUrl() . $this->getLoginUri($routeName) . '?ref=' . $request->getUrl());
        }
    }

    /**
     * Get login URI by route name
     * On success return login URI
     * On error throw exception
     * @param $routeName
     * @return bool|string
     * @throws \Exception
     */
    private function getLoginUri($routeName)
    {
        if(!$routeName && !$this->config['loginRouteName']){
            throw new \Exception('Login route name is not set.');
        }

        if (!$routeName) {
            $routeName = $this->config['loginRouteName'];
        }

        $uri = $this->router->getUrlFor($routeName);

        if(!$uri) {
            throw new \Exception('Login route name does not exist.');
        }

        return $uri;
    }


    /**
     * 302 redirect to given $path
     * @param $path
     */
    private function redirect($path)
    {
        header('HTTP/1.1 302 Found');
        header('Location:' . $path);
        exit;
    }
}