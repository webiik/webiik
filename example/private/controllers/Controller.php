<?php
namespace Webiik;

class Controller
{
    private $myClass;

    /**
     * Controller constructor.
     */
    public function __construct($response, $routeInfo, \MyClass $myClass, \MyClass $myClassTwo)
    {
        $this->myClass = $myClass;
        print_r($routeInfo);
        print_r($response);
    }

    public function launch()
    {
        echo '<br/>';
        $this->myClass->hello();
        echo 'test';
    }
}