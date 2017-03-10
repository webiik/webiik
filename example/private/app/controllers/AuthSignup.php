<?php
namespace Webiik;

class AuthSignup extends AuthBase
{
    private $flash;
    private $render;

    public function __construct(
        Flash $flash,
        Render $render,
        Auth $auth,
        Csrf $csrf,
        Router $router,
        Translation $translation
    )
    {
        parent::__construct($auth, $csrf, $router, $translation);
        $this->render = $render;
        $this->flash = $flash;
    }

    public function run()
    {
        // Get merged translations
        // We always get all shared translations and translations only for current page,
        // because Skeleton save resources and adds only these data to Translation class
        $translations = $this->translation->_tAll(false);

        // Try to sign up the user
        $resArr = $this->signup();

        // Add formatted form data to translation which we use further in template
        if (isset($resArr['form']['data'])) {
            $translations['form']['data'] = $resArr['form']['data'];
        }

        // Add the form inline error messages
        if (isset($resArr['form']['msg'])) {
            $translations['form']['msg'] = $resArr['form']['msg'];
        }

        // Add flash messages if there are some
        if (isset($resArr['msg'])) {
            foreach ($resArr['msg'] as $type => $messages) {
                foreach ($messages as $message) {
                    if ($type == 'err') {
                        $this->flash->addFlashNow($type, $message);
                    }
                    if ($type == 'ok' || $type == 'inf') {
                        $this->flash->addFlashNext($type, $message);
                    }
                }
            }
        }

        // If login requires activation
        if (isset($resArr['withActivation'])) {
            $this->auth->redirect($this->router->getUrlFor('login'));
        }

        // If user is successfully logged in
        if (isset($resArr['redirectUrl'])) {
            $this->auth->redirect($this->router->getUrlFor('account'));
        }

        // Render template
        echo $this->render->render(['signup.twig', $translations]);
    }
}