<?php
namespace Webiik;

class Controller
{
    private $routeInfo;
    private $translation;
    private $connection;
    private $router;
    private $auth;
    private $sessions;

    /**
     * Controller constructor.
     */
    public function __construct(
        $routeInfo,
        Connection $connection,
        Translation $translation,
        Router $router,
        Auth $auth,
        Sessions $sessions
    )
    {
        $this->routeInfo = $routeInfo;
        $this->translation = $translation;
        $this->connection = $connection;
        $this->router = $router;
        $this->auth = $auth;
        $this->sessions = $sessions;

        print_r($routeInfo);
    }

    public function run()
    {
        // Connect to DB
        $pdo = $this->connection->connect('user');

//        $this->sessions->setSessionDir(__DIR__ . '/../tmp');
//        $this->sessions->sessionStart();
//        $this->auth->setCookieName('myPC');
//        $this->auth->setWithActivation(true);
        //$this->auth->userGenerateActivationToken($uid);

//        print_r($this->auth->userSet('vlasta@mihal.me', 'test', 1));
//        $user = $this->auth->userGet('jiri@mihal.me', 'test');
//        $this->auth->userLogin($user['uid']);
        if($this->auth->isUserLogged()){
            echo 'AHOJ!';
        }

        if($this->auth->userCan('edit-post')){
            echo 'POST';
        }

//        print_r($this->auth->generateActivation(1));
//        echo $this->auth->userActivate('e5ad2d59d1fa', '2c319e4d888234eb89db621560dd2cfa');
//        echo $this->auth->userGet('jiri@mihal.me', 'test');

//        $this->auth->login(1, true);
//        $this->auth->userLogout();
//
//        if ($this->auth->userLogged()) {
//            echo 'Is logged in.';
//        }
//
//        if($this->auth->userCan('access-account')){
//            echo 'User can access account.';
//        }

//        if ($this->auth->isActivated(1)) {
//            echo 'Activated.';
//        } else {
//            echo 'Not activated.';
//        }


        // Get page translation
//        echo $this->translation->_p('t10', ['speed' => '100']);

        // Get route URI in some lang
        // Todo: Authenticate user (Think about how authentication will work - actions, MW)
        echo '<br/>CS URI: ' . $this->router->getUriFor('account', 'cs') . '<br/>';
        echo 'SK URI: ' . $this->router->getUriFor('account', 'sk') . '<br/>';
        echo 'EN URI: ' . $this->router->getUriFor('account', 'en') . '<br/>';

        // Todo: Render page using some template engine (Twig)
//        $this->view->render($template, $data);
        echo '<br/>';
        echo 'Controller';

        // Todo: PLUG-INS, COMPONENTS design
    }
}