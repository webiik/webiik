<?php
namespace Webiik;

class AuthLoginAjax extends AuthBase
{
    private $flash;

    public function __construct(
        Flash $flash,
        Auth $auth,
        Csrf $csrf,
        Router $router,
        Translation $translation
    )
    {
        parent::__construct($auth, $csrf, $router, $translation);
        $this->flash = $flash;
    }

    public function run()
    {
        // Try to login the user
        $resArr = $this->login();

        // Set JSON header
        $response = new Response();
        $response->setContentType('json');

        // Print out response as JSON
        echo json_encode($resArr);
    }
}