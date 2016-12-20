<?php
namespace Webiik;

class Account extends LoginLauncher
{
    public function __construct(
        Translation $translation,
        \Twig_Environment $twig,
        Auth $auth,
        Flash $flash,
        Csrf $csrf,
        Router $router,
        Sessions $sessions
    )
    {
        parent::__construct($translation, $twig, $auth, $flash, $csrf, $router);
        $this->sessions = $sessions;
    }

    public function run()
    {
        // If user can't perform given action, run login controller instead of current controller.
        // It is better than redirecting user to login route. But if want to use redirection then
        // remove LoginLauncher and this row and add route middleware AuthMw:userCan in /routes/routes.php
        $this->userCan('access-account');

        // Get merged translations
        // We always get all shared translations and translations only for current page,
        // Skeleton is smart and save resources, so adds only these data to Translation class
        $translations = $this->translation->_tAll(false);

        // Parse some values
        $translations['t1'] = $this->translation->_p('t1', ['timeStamp' => time()]);
        $translations['t2'] = $this->translation->_p('t2', ['numCats' => 1]);

        // Render page
        echo $this->twig->render('home.twig', $translations);
    }
}