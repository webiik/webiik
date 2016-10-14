<?php
namespace Webiik;

class Login
{
    /**
     * @var Sessions
     */
    private $sessions;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Flash
     */
    private $flash;

    public function __construct(Sessions $sessions, Router $router, Auth $auth, Flash $flash)
    {
        $this->sessions = $sessions;
        $this->router = $router;
        $this->auth = $auth;
        $this->flash = $flash;
    }

    private function getReferrer()
    {
        if (isset($_GET['ref'])) {
            $referrer = $_GET['ref'];
        } elseif (isset($_POST['ref'])) {
            $referrer = $_POST['ref'];
        } else {
            $referrer = $this->router->getUrlFor('account');
        }

        return $referrer;
    }

    public function run()
    {
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $pswd = isset($_POST['pswd']) ? $_POST['pswd'] : '';
        $referrer = $this->getReferrer();

        if ($_POST) {

            $user = $this->auth->userGet($_POST['email'], $_POST['pswd']);

            if (is_array($user)) {

                $this->sessions->sessionStart();

                $uid = $user['uid'];
                $this->auth->userLogin($uid);

                if (!$this->auth->redirect($referrer)) {
                    $this->auth->redirect($this->router->getUrlFor('account'));
                }

            } else {

                if ($user == -3) {
                    $this->flash->setFlashNow('err', 'Too many login attempts.');
                }

                if ($user == -2) {
                    $this->flash->setFlashNow('err', 'User does not exist.');
                }

                if ($user == -1) {
                    $this->flash->setFlashNow('err', 'User account expired.');
                }

                if ($user == 0) {
                    $this->flash->setFlashNow('err', 'Invalid password.');
                }

                print_r($this->flash->getFlashes());

            }
        }

        echo '<form action="" method="post">';
        echo '<input type="text" name="email" placeholder="email" value="' . $email . '">';
        echo '<input type="password" name="pswd" placeholder="password" value="' . $pswd . '">';
        echo '<input type="hidden" name="ref" value="' . $referrer . '">';
        echo '<input type="submit" value="login">';
        echo '</form>';
    }
}