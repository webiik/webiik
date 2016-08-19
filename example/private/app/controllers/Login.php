<?php
namespace Webiik;

class Login
{
    /** @var Authentication @inject */
    private $authentication;

    /** @var Flash @inject */
    private $flash;

    public function init()
    {
        if ($_POST) {
            if (isset($_POST['email']) && isset($_POST['pswd'])) {

                $loginRes = $this->authentication->loginEmailPswd($_POST['email'], $_POST['pswd']);

                if ($loginRes === true) {

                    $this->authentication->redirect('./');

                } else {

                    // Handle error...

                }
            }
        }
    }
}