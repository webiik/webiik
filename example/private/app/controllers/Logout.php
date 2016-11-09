<?php
namespace Webiik;

class Logout
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Flash
     */
    private $flash;

    /**
     * @var Translation
     */
    private $translation;

    public function __construct(Router $router, Auth $auth, Flash $flash, Translation $translation)
    {
        $this->router = $router;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->translation = $translation;
    }

    public function run()
    {
        $this->auth->userLogout();
        $this->flash->addFlashNext('ok', $this->translation->_t('logout'));
        $this->auth->redirect($this->router->getUrlFor('login'));
    }
}