<?php
namespace Webiik;

class AuthMiddleware
{
    // Kontrola prihlaseni a akci uzivatele

    /**
     * @var Connection
     */
    private $connection;

    function __invoke($response, $next, Connection $connection, $action = false)
    {
        $this->connection = $connection;
        $action ? $this->must($action) : $this->auth();
        $next($response);
    }

    /**
     * Return array with user info
     * @return $this
     */
    public function user()
    {
        return $this;
    }

    /**
     * Redirect not logged user to login page
     * @return bool
     */
    public function auth()
    {
        if ($this->isAuth()) {
            return true;
        }

        // Redirect to login page
    }

    /**
     * Check if user is authenticated
     * @return bool
     */
    public function isAuth()
    {
        // Return true, false
    }

    public function can($action)
    {
        // Return true, false
    }

    public function must($action)
    {
        // Return true, otherwise redirect user to login page
    }
}