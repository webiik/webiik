<?php
namespace Webiik;

class Login
{
    /** @var $routeInfo */
    private $routeInfo;

    /** @var Authentication */
    private $authentication;

    /** @var Flash */
    private $flash;

    public function __construct(Authentication $authentication, Flash $flash, $routeInfo)
    {
        $this->routeInfo = $routeInfo;
        $this->authentication = $authentication;
        $this->flash = $flash;
    }

    public function run()
    {
        print_r($this->routeInfo);

        echo '<form action="" method="post">';
        echo '<input type="text" name="email" value="">';
        echo '<input type="text" name="pswd" value="">';
        echo '<input type="submit" value="login">';
        echo '</form>';

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