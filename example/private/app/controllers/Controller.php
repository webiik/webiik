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
    public function __construct($response, $routeInfo, Translation $trans)
    {
        $this->routeInfo = $routeInfo;
        $this->trans = $trans;
//        $this->connection = $connection;

        print_r($routeInfo);
        print_r($response);
    }

    public function run()
    {
        // Todo: Get link to route in some lang

        // Load translations
        //$pdo = $this->connection->connect('ad');
        //$routeName = $this->routeInfo['name'] ? $this->routeInfo['name'] : $this->routeInfo['id'];
        //$this->trans->addTrans($this->routeInfo['lang'], $this->routeInfo['tsFile']);

        echo '<br/>';
        echo 'Controller';
    }
}