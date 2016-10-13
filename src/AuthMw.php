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
     * Auth constructor.
     * @param Sessions $sessions
     */
    public function __construct(Sessions $sessions, Connection $connection)
    {
        $this->sessions = $sessions;
        $this->connection = $connection;
    }

    public function login($uid)
    {
        $this->sessions->sessionRegenerateId();
        $this->sessions->addToSession('logged', $uid);
    }

    public function logout()
    {
        $this->sessions->sessionDestroy();
    }

    public function isLogged()
    {
        return $this->sessions->getFromSession('logged');
    }

    public function can($request, $next, $action)
    {
        // Look into db and check if user can do the action
        echo 'User can ';
        echo $action;
        $canUserDoTheAction = false;

        if (!$canUserDoTheAction) {
            //$this->redirect();
            exit;
        }

        $next($request);
    }

    public function must($action)
    {
        echo 'User must ';
        echo $action;
    }

}