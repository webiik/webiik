<?php
namespace Webiik;

class Controller
{
    private $routeInfo;
    private $translation;
    private $connection;
    private $router;
    private $auth;

    /**
     * Controller constructor.
     */
    public function __construct(
        $routeInfo,
        Connection $connection,
        Translation $translation,
        Router $router,
        Auth $auth
    )
    {
        $this->routeInfo = $routeInfo;
        $this->translation = $translation;
        $this->connection = $connection;
        $this->router = $router;
        $this->auth = $auth;

        print_r($routeInfo);
    }

    public function run()
    {
        // Connect to DB
//        $pdo = $this->connection->connect('db1');

        // Get page translation
//        echo $this->translation->_p('t10', ['speed' => '100']);

        $this->auth->can('use-uri-for');

        // Get route URI in some lang
        // Todo: Authenticate user (Think about how authentication will work - actions, MW)
        //if ($this->auth->can('use-uri-for')) {
        echo '<br/>CS URI: ' . $this->router->getUriFor('account', 'cs') . '<br/>';
        echo 'SK URI: ' . $this->router->getUriFor('account', 'sk') . '<br/>';
        echo 'EN URI: ' . $this->router->getUriFor('account', 'en') . '<br/>';
//        }

        // Todo: Render page using some template engine (Twig)
//        $this->view->render($template, $data);
        echo '<br/>';
        echo 'Controller';

        // Todo: PLUG-INS, COMPONENTS design
    }
}