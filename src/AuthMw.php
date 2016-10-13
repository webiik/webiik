<?php
namespace Webiik;

class AuthMw
{
    /**
     * @var Sessions
     */
    private $sessions;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var string Permanent login cookie name
     */
    private $cookieName = 'PC';

    public function __construct(Sessions $sessions, Connection $connection, Router $router)
    {
        $this->sessions = $sessions;
        $this->connection = $connection;
        $this->router = $router;
    }

    public function setCookieName($string)
    {
        $this->cookieName = $string;
    }

    public function isLogged(Request $request, \Closure $next, $routeName = false)
    {
        if ($this->sessions->getFromSession('logged') || $this->isPermentlyLogged()) {
            $next($request);
        }
        $this->redirect($request->getRootUrl() . $this->getLoginUri($routeName));
    }

    public function can(Request $request, \Closure $next, $action, $routeName = false)
    {
        if (!$this->sessions->getFromSession('logged')) {
            // Todo: Uncomment
//            $this->redirect($request->getRootUrl() . $this->router->getUrlFor('login'));
        }

        // Todo: Look into db and check if user can do the action
        $connNames = $this->connection->getConnectionNames();
        $this->connection->connect($connNames[0]);
        echo 'User can ';
        echo $action;
        $canUserDoTheAction = true;

        if (!$canUserDoTheAction) {
            $this->redirect($request->getRootUrl() . $this->getLoginUri($routeName));
        }

        $next($request);
    }

    // Todo: Check if user is permanently logged in
    private function isPermentlyLogged()
    {
        return false;
    }

    private function getLoginUri($routeName)
    {
        if($routeName){
            $uri = $this->router->getUrlFor($routeName);
        } else {
            $uri = $this->router->getUrlFor('login');
        }

        return $uri;
    }

    private function redirect($path)
    {
        header('HTTP/1.1 302 Found');
        header('Location:' . $path);
        exit;
    }
}