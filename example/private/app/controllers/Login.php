<?php
namespace Webiik;

class Login
{
    /** @var $routeInfo @inject */
    public $routeInfo;

    /** @var Authentication */
    private $authentication;

    /** @var Flash */
    private $flash;

//    public function injectAuthentication(Authentication $authentication){
//        $this->authentication = $authentication;
//    }

    public function injectFlash(Flash $flash, Authentication $authentication){
        $this->flash = $flash;
        $this->authentication = $authentication;
    }

//    public function __construct(Flash $flash, $routeInfo)
    public function __construct($routeInfo)
    {
//        $this->flash = $flash;
//        $this->flash->setWrap('err', '<div></div>');
        print_r($routeInfo);
    }


    public function run()
    {
//        echo 'A';
//        print_r($this->routeInfo);
        $this->authentication->logout();
        $this->flash->setWrap('err', '<div></div>');

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