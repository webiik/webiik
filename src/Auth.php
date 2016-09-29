<?php
namespace Webiik;

class Auth
{
    /**
     * @var Sessions
     */
    private $sessions;

    private $afterLoginRoute = '';

    /**
     * Auth constructor.
     * @param Sessions $sessions
     */
    public function __construct(Sessions $sessions)
    {
        $this->sessions = $sessions;
    }

    public function login($uid)
    {
        $this->sessions->addToSession('logged', $uid);
    }

    public function isLogged()
    {
        return $this->sessions->getFromSession('logged');
    }

    public function logout()
    {
        $this->sessions->sessionDestroy();
    }

    public function redirect()
    {
        echo 'Ahoj!';
    }

    public function can($p1, $p2 = false, $p3 = false)
    {
        // Cheap determination of method calling
        if ($p1 instanceof Request) {

            // Called as middleware
            $request = $p1;
            $next = $p2;
            $action = $p3;

        } else {

            // Called as classic method
            $action = $p1;
        }

        // Free memory
        $p1 = $p2 = $p3 = false;

        // Look into db and check if user can do the action
        echo 'User can ';
        echo $action;
        $canUserDoTheAction = false;

        // Middleware action
        if (isset($next) && isset($request) && $next instanceof \Closure) {

            if(!$canUserDoTheAction){
                $this->redirect();
            }

            $next($request);
        }

        return $canUserDoTheAction;
    }

    public function must($action)
    {
        echo 'User must ';
        echo $action;
    }

}