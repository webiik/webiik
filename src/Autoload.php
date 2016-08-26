<?php
namespace Webiik;

/**
 * Register/add new spl atoloader
 * Class Autoload
 * @package Webiik
 */
class Autoload
{
    /**
     * Directories where we will find classes
     * @var array
     */
    private $dirs = [];

    /**
     * Register own autoloader
     */
    public function __construct()
    {
        spl_autoload_register(function ($class) {
            $class = explode('\\', $class);
            foreach ($this->dirs as $dir) {
                if (file_exists($dir . $class[1] . '.php')) {
                    include_once($dir . $class[1] . '.php');
                }
            }
        });
    }

    /**
     * Add dir for autoload
     * @param $dir
     */
    public function addDir($dir)
    {
        $this->dirs[] = rtrim($dir, '/') . '/';
    }
}