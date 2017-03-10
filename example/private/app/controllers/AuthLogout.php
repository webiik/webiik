<?php
namespace Webiik;

class AuthLogout
{
    private $translation;
    private $auth;
    private $flash;
    private $router;

    public function __construct(Translation $translation, Auth $auth, Flash $flash, Router $router)
    {
        $this->translation = $translation;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->router = $router;
    }

    public function run()
    {
        $this->auth->userLogout();
        $this->flash->addFlashNext('ok', $this->translation->_t('logout'));
        $this->auth->redirect($this->router->getUrlFor('login'));
    }
}