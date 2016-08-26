<?php
namespace Webiik;

/**
 * Class Config
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Config
{
    /**
     * Read config file and return config array
     * @return array
     */
    public static function loadConfig($dir)
    {
        $config = [];
        $dir = rtrim($dir, '/');
        $configServer = $dir . '/config.php';
        $configLocal = $dir . '/config.local.php';

        $config = require $configServer;

        if (file_exists($configLocal)) {
            $config = require $configLocal;
        }

        return $config;
    }
}