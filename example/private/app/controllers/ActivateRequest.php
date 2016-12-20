<?php
namespace Webiik;

class ActivateRequest
{
    private $translation;
    private $auth;
    private $flash;
    private $router;
    private $sessions;
    private $config;

    public function __construct(
        Translation $translation,
        Auth $auth,
        Flash $flash,
        Router $router,
        Sessions $sessions,
        $config
    )
    {
        $this->translation = $translation;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->router = $router;
        $this->sessions = $sessions;
        $this->config = $config['accounts'];
    }

    public function run()
    {
        // Get user id
        $uid = $this->sessions->getFromSession('resend');

        // Can user show this page
        if (!$uid) {

            // Determine redirect route base on user login status
            if($this->auth->isUserLogged()){
                $routeName = $this->config['routes']['defaultAfterLoginRouteName'];
            } else {
                $routeName = $this->config['routes']['loginRouteName'];
            }

            header('HTTP/1.1 302 Found');
            header('Location:' . $this->router->getUrlFor($routeName));
            exit;
        }

        // Todo: Get activation token
        $data = $this->auth->userActivationGenerateToken($uid);

        // Todo: Send activation email

        // Todo: Flash from translations
        $this->flash->addFlashNext('inf', 'Email with activation link was sent to your email box.');

        // Redirect back to route activate
        header('HTTP/1.1 302 Found');
        header('Location:' . $this->router->getUrlFor('activate'));
        exit;
    }
}