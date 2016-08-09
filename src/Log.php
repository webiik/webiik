<?php
namespace Webiik;

/**
 * Class Log
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Log
{
    /**
     * @var array
     */
    private $loggers = [];

    /**
     * @param string $name
     * @param object $logger
     */
    public function add($name, $logger)
    {
        if(!isset($this->loggers[$name])){
            $this->loggers[$name] = $logger;
        }
    }

    /**
     * @param string $name
     * @return Logger
     */
    public function get($name)
    {
        return $this->loggers[$name];
    }
}