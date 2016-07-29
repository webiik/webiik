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
    public function loadConfig()
    {
        $config = [];
        $configServer = $this->getWebRootPath() . '/private/config/config.php';
        $configLocal = $this->getWebRootPath() . '/private/config/config.local.php';

        if (file_exists($configLocal)) {
            require $configLocal;
        } else {
            require $configServer;
        }

        return $config;
    }

    /**
     * Get path of the executing script
     * @return string
     */
    private function getWebRootPath()
    {
        return $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['SCRIPT_NAME']);
    }
}