<?php
namespace Webiik;

class BaseLogin
{
    protected $auth;
    protected $csrf;
    protected $router;
    protected $config;

    public function __construct(
        Auth $auth,
        Csrf $csrf,
        Router $router,
        $config
    )
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->router = $router;
        $this->config = $config['accounts'];
    }

    protected function redirect($url, $allowReferrer = false)
    {
        $uris = [];

        if ($allowReferrer) {
            foreach ($this->config['routes'] as $key => $routeName) {
                $uris[] = $this->router->getUriFor($routeName);
            }
        }

        $this->auth->confURIs($uris);
        $this->auth->redirect($url, $allowReferrer);
    }

    protected function csrf()
    {
        // CSRF protection
        if ($_POST) {

            if (!isset($_POST[$this->csrf->getTokenName()])
                || !$this->csrf->validateToken($_POST[$this->csrf->getTokenName()])
            ) {
                return false;
            }

            $this->csrf->setToken();

        } else {

            $this->csrf->setToken();
        }

        return true;
    }
}