<?php
namespace Webiik;

class Signup
{
    /** @var Authentication @inject */
    private $authentication;

    /** @var Flash @inject */
    private $flash;

    public function init()
    {
        if ($_POST) {

            if ($signup) {

                $this->authentication->login($uid);
                $this->authentication->redirect('./');

            } else {

                // Handle error...

            }
        }
    }
}