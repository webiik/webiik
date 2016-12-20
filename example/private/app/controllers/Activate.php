<?php
namespace Webiik;

class Activate extends BaseLogin
{
    private $translation;
    private $twig;
    private $flash;
    private $sessions;

    public function __construct(
        Translation $translation,
        \Twig_Environment $twig,
        Auth $auth,
        Flash $flash,
        Csrf $csrf,
        Router $router,
        Sessions $sessions,
        $config
    )
    {
        parent::__construct($auth, $csrf, $router, $config);
        $this->translation = $translation;
        $this->twig = $twig;
        $this->flash = $flash;
        $this->sessions = $sessions;
    }

    public function run()
    {
        // Can user show this page
        if (!$this->sessions->getFromSession('resend')) {

            // Determine redirect route base on user login status
            if($this->auth->isUserLogged()){
                $routeName = $this->config['routes']['defaultAfterLoginRouteName'];
            } else {
                $routeName = $this->config['routes']['loginRouteName'];
            }

            $this->redirect($this->router->getUrlFor($routeName));
        }

        // Todo: Fix flashes

        // Get merged translations
        // We always get all shared translations and translations only for current page,
        // because Skeleton save resources and adds only these data to Translation class
        $translations = $this->translation->_tAll(false);

        // Render template
        echo $this->twig->render('activate.twig', $translations);
    }
}