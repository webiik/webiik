<?php
namespace Webiik;

/**
 * Class Autoload
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Autoload
{
    /**
     * Directories where we will search classes
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