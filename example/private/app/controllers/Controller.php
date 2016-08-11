<?php
namespace Webiik;

class Controller
{
    private $trans;
    private $connection;
    private $routeInfo;

    /**
     * Controller constructor.
     */
    public function __construct($response, $routeInfo)
    {
        $this->routeInfo = $routeInfo;
//        $this->trans = $trans;
//        $this->connection = $connection;

        print_r($routeInfo);
        print_r($response);
    }

    public function run()
    {
        // Todo: Connect to DB
        //$pdo = $this->connection->connect('db1');

        // Todo: Authenticate user (Think about how authentication will work - actions, roles, MW)

        // Todo: Get page translation

        // Todo: Get link to route in some lang

        // Load translations

        // Render page using some template engine (Twig)

        //$routeName = $this->routeInfo['name'] ? $this->routeInfo['name'] : $this->routeInfo['id'];
        //$this->trans->addTrans($this->routeInfo['lang'], $this->routeInfo['tsFile']);

        echo '<br/>';
        echo 'Controller';
    }
}