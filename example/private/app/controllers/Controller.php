<?php
namespace Webiik;

class Controller
{
    private $translation;
    private $connection;
    private $routeInfo;
    private $router;

    /**
     * Controller constructor.
     */
    public function __construct(
        $response,
        $routeInfo,
        Connection $connection,
        Translation $translation,
        Router $router,
        AuthMiddleware $auth
    )
    {
        $this->routeInfo = $routeInfo;
        $this->translation = $translation;
        $this->connection = $connection;
        $this->router = $router;
        $this->auth = $auth;

        print_r($routeInfo);
        print_r($response);
    }

    public function run()
    {
        // Connect to DB
        $pdo = $this->connection->connect('db1');

        // Todo: Authenticate user (Think about how authentication will work - actions, MW)

        // Get page translation
        echo $this->translation->_p('t10', ['speed' => '100']);

        // Get route URI in some lang
        //if ($this->auth->user()->can('use-uri-for')) {
        echo '<br/>CS URI: ' . $this->router->getUriFor('account', 'cs') . '<br/>';
        echo 'SK URI: ' . $this->router->getUriFor('account', 'sk') . '<br/>';
        echo 'EN URI: ' . $this->router->getUriFor('account', 'en') . '<br/>';
//        }

        echo '<form action="" method="post">';
        echo '<input type="text" name="email" value="">';
        echo '<input type="text" name="pswd" value="">';
        echo '<input type="submit" value="login">';
        echo '</form>';

        $a = 5;

        if($a == true){
            echo $a;
        }

        if ($_POST) {
            $this->user->login($_POST['email'], $_POST['pswd']);
        }

        // Todo: Render page using some template engine (Twig)
        echo '<br/>';
        echo 'Controller';

        // Todo: PLUG-INS, COMPONENTS design
    }
}