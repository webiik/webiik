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
     * LoadTranslations constructor.
     * @param Translation $translation
     * @param Route $route
     */
    public function __construct(Translation $translation, Route $route)
    {
        $this->translation = $translation;
        $this->route = $route;
    }

    /**
     * @param \Webiik\Data\Data $data
     * @param callable $next
     */
    public function run(\Webiik\Data\Data $data, callable $next): void
    {
        // Load shared translation file
        if (file_exists(WEBIIK_BASE_DIR . '/translations/' . WEBIIK_LANG . '/_app.php')) {
            $translation = require_once WEBIIK_BASE_DIR . '/translations/' . WEBIIK_LANG . '/_app.php';
            $this->translation->addArr($translation);
        };

        // Load route related translation file
        $filename = WEBIIK_BASE_DIR . '/translations/' . WEBIIK_LANG . '/' . strtolower($this->route->getName()) . '.php';
        if ($this->route->getName() && file_exists($filename)) {
            $translation = require_once $filename;
            $this->translation->addArr($translation);
        }

        $next();
    }
}