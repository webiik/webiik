<?php
namespace Webiik;

class LoginLauncher
{
    protected $translation;
    protected $auth;
    protected $router;
    protected $flash;
    protected $csrf;
    protected $twig;

    /**
     * Controller constructor.
     */
    public function __construct(
        Translation $translation,
        \Twig_Environment $twig,
        Auth $auth,
        Flash $flash,
        Csrf $csrf,
        Router $router
    )
    {
        $this->translation = $translation;
        $this->twig = $twig;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->router = $router;
    }

    /**
     * If user can't perform given action, run login controller instead of current controller
     * @param $action
     */
    public function userCan($action)
    {
        if (!$this->auth->userCan($action)) {

            if (!$_POST) {
                $this->flash->addFlashNow('err', $this->translation->_t('msgUnauthorised'));
            }

            $loginController = new Login($this->translation, $this->twig, $this->auth, $this->flash, $this->csrf, $this->router);
            $loginController->run();
            exit;
        }
    }
}