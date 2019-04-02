<?php
declare(strict_types=1);

namespace Webiik\Middleware\Core;

use Webiik\Router\Route;
use Webiik\Translation\Translation;

class LoadTranslations
{
    /**
     * @var Translation
     */
    private $translation;


    /**
     * @var Route
     */
    private $route;

    /**
     * @var array
     */
    private $wpExtensions;

    /**
     * LoadTranslations constructor.
     * @param Translation $translation
     * @param Route $route
     * @param $wpExtensions
     */
    public function __construct(Translation $translation, Route $route, array $wpExtensions)
    {
        $this->translation = $translation;
        $this->route = $route;
        $this->wpExtensions = $wpExtensions;
    }

    /**
     * @param \Webiik\Data\Data $data
     * @param callable $next
     */
    public function run(\Webiik\Data\Data $data, callable $next): void
    {
        // Is it an extension? Determine it by controller
        if (preg_match('~^\\\WE\\\([\w_-]+)\\\~', $this->route->getController()[0], $match)) {
            $extensionName = $match[1];
        }

        // Load main application translations only if it is not an extension
        if(!isset($extensionName)) {
            // Add main app shared translation file
            if (file_exists(WEBIIK_BASE_DIR . '/translations/' . WEBIIK_LANG . '/_shared.php')) {
                $translation = require_once WEBIIK_BASE_DIR . '/translations/' . WEBIIK_LANG . '/_shared.php';
                $this->translation->addArr($translation);
            };

            // Add main app route related translation file
            $filename = WEBIIK_BASE_DIR . '/translations/' . WEBIIK_LANG . '/' . strtolower($this->route->getName()) . '.php';
            if ($this->route->getName() && file_exists($filename)) {
                $translation = require_once $filename;
                $this->translation->addArr($translation);
            }
        }

        // Load extension translations only if it is an extension
        if (isset($extensionName)) {
            foreach ($this->wpExtensions as $dirname => $extUri) {

                // Load extension translations only if extension name is same as extension dir
                if ($extensionName != $dirname) {
                    continue;
                }

                // Add extensions' shared translation file
                if (file_exists(WEBIIK_BASE_DIR . '/../extensions/' . $dirname . '/translations/' . WEBIIK_LANG . '/_shared.php')) {
                    $translation = require_once WEBIIK_BASE_DIR . '/../extensions/' . $dirname . '/translations/' . WEBIIK_LANG . '/_shared.php';
                    $this->translation->addArr($translation);
                };

                // Add extensions' route related translation file
                $filename = WEBIIK_BASE_DIR . '/../extensions/' . $dirname . '/translations/' . WEBIIK_LANG . '/' . $this->route->getName() . '.php';
                if ($this->route->getName() && file_exists($filename)) {
                    $translation = require_once $filename;
                    $this->translation->addArr($translation);
                }
            }
        }

        $next();
    }
}