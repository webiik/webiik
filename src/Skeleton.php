<?php
namespace Webiik;

/**
 * Class Skeleton
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Skeleton extends Core
{
    /**
     * Current language determined from URI
     * @var
     */
    private $lang;

    /**
     * Skeleton constructor.
     */
    public function __construct($config)
    {
        parent::__construct();

        // Add config array to container
        $this->addParam('config', $config);

        // Add basic app services

        // Add Sessions
        $this->addService('Webiik\Sessions', function ($c) {
            return new Sessions();
        });

        // Add Request
        $this->addService('Webiik\Request', function ($c) {
            return new Request();
        });

        // Add Authentication
        $this->addService('Webiik\Auth', function ($c) {
            return new Auth($c['Webiik\Sessions']);
        });

        // Add Flash messages
        $this->addService('Webiik\Flash', function ($c) {
            return new Flash();
        });

        // Add Connection
        $this->addService('Webiik\Connection', function ($c) {
            $connection = new Connection();
            if (isset($c['config']['database'])) {
                foreach ($c['config']['database'] as $name => $p) {
                    $connection->add($name, $p[0], $p[1], $p[2], $p[3], $p[4]);
                }
            }
            return $connection;
        });

        // Add template engine
        $this->addService('Webiik\Render', function ($c) {
            // Todo: Configure render engine
            return new Render();
        });

        // Add Conversion
        $this->addService('Webiik\Conversion', function ($c) {
            return new Conversion();
        });

        // Add Translation
        $this->addService('Webiik\Translation', function ($c) {
            return new Translation();
        });

        // Add Filesystem
        $this->addService('Webiik\Filesystem', function ($c) {
            return new Filesystem();
        });

        // Set app main lang (can return 404)
        $this->setLang();
        $this->trans()->setLang($this->lang);

        // Set fallback languages
        $this->setFallbackLangs();
    }

    public function run()
    {
        // Load route definitions for current lang from file.
        // If routes definitions exist, map routes that has translation(incl. fallbacks).
        // Load route translations in current lang
        $this->mapTranslatedRoutes();

        // Match URI against mapped routes
        $routeInfo = $this->router()->match();

        // If there is no match, show error page and exit
        if ($routeInfo['http_status'] == 404) $this->error(404);
        if ($routeInfo['http_status'] == 405) $this->error(405);

        // Store route info into container to allow its injection in route handlers and middlewares
        $this->addParam('routeInfo', $routeInfo);

        // Load app and current page translations in to Translation
        $this->loadTranslation($this->lang, '_app');
        $this->loadTranslation($this->lang, $routeInfo['name']);

        // Load translation formats in to Translation
        $this->loadFormats($this->lang);

        // Load app and page conversions in to Conversion
        $this->loadConversions('_app');
        $this->loadConversions($routeInfo['name']);

        // Map rest of routes with empty controllers.
        // Webiik needs this step to provide getUriFor() for every route in every lang.
        $this->mapEmptyTranslatedRoutes();

        // Instantiate middleware
        $middleware = new Middleware($this->container);

        // Add route handler to be run after last middleware
        $middleware->addDestination($routeInfo['handler']);

        // Add app and route middlewares
        $appMiddleawares = isset($this->middlewares['app']) ? array_reverse($this->middlewares['app']) : [];
        $routeMiddlewares = isset($this->middlewares[$routeInfo['id']]) ? array_reverse($this->middlewares[$routeInfo['id']]) : [];
        $middleware->add(array_merge($appMiddleawares, $routeMiddlewares));

        // Run
        $middleware->run();
    }

    /**
     * Return translation for given key and lang.
     * If translation does not exist, try to load fallback translation from given file.
     * Return false if translation does not exist in any lang.
     * @param string $file
     * @param string $key
     * @param int $fallbackIndex
     * @return bool|mixed
     */
    public function _t($file, $key, $lang, $fallbackIndex = 0)
    {
        $trans = $this->trans();
        $trans->setLang($lang);
        $t = $trans->_t($key);
        if (!$t) {
            $fl = $this->getFallbackLangs($lang);
            if ($fl && isset($fl[$fallbackIndex])) {
                $this->loadTranslation($fl[$fallbackIndex], $file, $key);
                $fallbackIndex++;
                $t = $this->_t($file, $key, $lang, $fallbackIndex);
            }
        }
        $trans->setLang($this->lang);
        return $t;
    }

    /**
     * Try to find lang in URI and compare that lang with available languages.
     * If language in URI is valid lang, set that lang as current lang.
     * If there is no language in URI and dlInUri is false, use default lang as current lang.
     * If there is no language in URI and dlInUri is true return 404 error page.
     */
    private function setLang()
    {
        $langs = $this->container['config']['language'];

        // Get web root URI
        $uri = str_replace($this->getScriptDir(), '', $_SERVER['REQUEST_URI']);

        // Get lang from web root URI
        preg_match('/^\/([\w]{2})\/|^\/([\w]{2})$/', $uri, $matches);

        // Did we find some language in web root URI?
        if (count($matches) > 0) {
            // Yes we do. So check if the lang is valid lang.
            foreach ($langs as $lang => $prop) {
                if ($lang == $matches[1]) break;
            }
        }

        // If we didn't find any language, it can be still ok, if default language
        // doesn't need to be in URI, otherwise page doesn't exist.
        if (!isset($lang) && !$this->container['config']['dlInUri']) {
            $lang = key($langs);
        } elseif (!isset($lang) && !$this->container['config']['dlInUri']) {
            $this->error(404);
        }

        $this->lang = $lang;
    }

    /**
     * Set fallback language(s) for all available languages
     */
    private function setFallbackLangs()
    {
        $langs = $this->container['config']['language'];
        foreach ($langs as $lang => $p) {
            if (isset($p[1]) && is_array($p[1])) {
                $this->trans()->setFallbacks($lang, $p[1]);
            }
        }
    }

    /**
     * Get array of fallback languages for given lang
     * @param string $lang
     * @return bool|array
     */
    private function getFallbackLangs($lang)
    {
        $langs = $this->container['config']['language'];

        if (isset($langs[$lang][1]) && is_array($langs[$lang][1])) {
            $fallbackLangs = $langs[$lang][1];
        } else {
            $fallbackLangs = false;
        }

        return $fallbackLangs;
    }

    /**
     * If translation file exists, add all translations from that file to Translation.
     * If key is set, add to Translation only translation for that key.
     * If key is set and does not exist, don't add any translation.
     * Save memory by using keys.
     * @param string $lang
     * @param bool|string $key
     * @param string $file
     */
    private function loadTranslation($lang, $file, $key = false)
    {
        $file = $this->container['config']['folder']['translations'] . '/' . $file . '.' . $lang . '.php';

        if (file_exists($file)) {

            $trans = $this->trans();
            $val = require $file;

            // If key exists, get value from translation array by dot notation signature
            if ($key) {
                $pieces = explode('.', $key);
                foreach ($pieces as $piece) {
                    if (isset($val[$piece])) {
                        $val = $val[$piece];
                    } else {
                        $val = false;
                    }
                }
            }

            if ($val) {
                $trans->addTrans($lang, $val, $key);
            }
        }
    }

    /**
     * If conversion file exists, add all conversions from that file to Conversion
     * @param string $file
     */
    private function loadConversions($file)
    {
        $dir = $this->container['config']['folder']['conversions'];

        if (file_exists($dir . '/' . $file . '.php')) {

            $conversions = require $dir . '/' . $file . '.php';
            $this->conv()->addConvArr($conversions);

            // Add conversion capability to Translation
            $this->trans()->addConv($this->conv());
        }
    }

    /**
     * If formats file exists, add all formats from that file to Translation
     * @param string $lang
     * @throws \Exception
     */
    private function loadFormats($lang)
    {
        $dir = $this->container['config']['folder']['formats'];

        if (file_exists($dir . '/' . $lang . '.php')) {

            $arr = require $dir . '/' . $lang . '.php';

            foreach ($arr as $type => $formats) {

                if ($type == 'date' || $type == 'time' || $type == 'number' || $type == 'currency') {
                    foreach ($formats as $name => $pattern) {
                        if ($type == 'date') $this->trans()->addDateFormat($lang, $name, $pattern);
                        if ($type == 'time') $this->trans()->addTimeFormat($lang, $name, $pattern);
                        if ($type == 'number') $this->trans()->addNumberFormat($lang, $name, $pattern);
                        if ($type == 'currency') $this->trans()->addCurrencyFormat($lang, $name, $pattern);
                    }
                }

                if ($type == 'monthsLong') $this->trans()->setLongMonthNamesTrans($formats, $lang);
                if ($type == 'monthsShort') $this->trans()->setShortMonthNamesTrans($formats, $lang);
                if ($type == 'daysLong') $this->trans()->setLongDayNamesTrans($formats, $lang);
                if ($type == 'daysShort') $this->trans()->setShortDayNamesTrans($formats, $lang);
            }
        }
    }

    /**
     * If route definition file exists, return route definitions, otherwise return false
     * @param $lang
     * @return bool|mixed
     */
    private function loadRoutes($lang)
    {
        $dir = $this->container['config']['folder']['routes'];

        if (file_exists($dir . '/routes.' . $lang . '.php')) {
            $routes = require $dir . '/routes.' . $lang . '.php';
        } elseif (file_exists($dir . '/routes.php')) {
            $routes = require $dir . '/routes.php';
        }

        return isset($routes) ? $routes : false;
    }

    /**
     * Map all routes in current lang, that has the translation or fallback translation.
     */
    private function mapTranslatedRoutes()
    {
        // Load route definitions in current language
        $routes = $this->loadRoutes($this->lang);
        if ($routes) {

            // Load translation of route definitions in current language
            $this->loadTranslation($this->lang, '_app', 'routes');

            // Set router to current lang, so route will be mapped in this lang
            $this->router()->setLang($this->lang);

            // Iterate all loaded route definitions
            // If route definition has translation or fallback translation, map it
            foreach ($routes as $name => $p) {
                $uri = $this->_t('_app', 'routes.' . $name, $this->lang);
                if ($uri) {
                    $uri = '/' . trim($uri, '/');
                    $route = $this->map($p['methods'], $uri, $p['controller'], $name);
                    if (isset($p['middlewares'])) {
                        foreach ($p['middlewares'] as $mw => $params) {
                            $route->add($mw, $params);
                        }
                    }
                }
            }
        }
    }

    /**
     * Map all routes, except routes in current lang, that has the translation or fallback translation.
     * Map them with an empty controller, we don't need more for translation.
     * @throws \Exception
     */
    private function mapEmptyTranslatedRoutes()
    {
        foreach ($this->container['config']['language'] as $lang => $prop) {

            // Iterate all langs except current lang, because routes for current lang are already loaded
            if ($lang != $this->lang) {

                // Load route definitions in iterated language
                $routes = $this->loadRoutes($lang);
                if ($routes) {

                    // Load translation of route definitions in iterated language
                    $this->loadTranslation($lang, '_app', 'routes');

                    // Set router to iterated lang, so route will be mapped in that lang
                    $this->router()->setLang($lang);

                    // Iterate all loaded route definitions
                    // If route definition has translation or fallback translation, map it
                    foreach ($routes as $name => $p) {
                        $uri = $this->_t('_app', 'routes.' . $name, $lang);
                        if ($uri) {
                            $uri = '/' . trim($uri, '/');
                            $this->router()->map(['GET'], $uri, '', $name);
                        }
                    }
                }
            }
        }
        // Set router back to current lang
        $this->router()->setLang($this->lang);
    }

    /**
     * @return Translation
     */
    private function trans()
    {
        return $this->container['Webiik\Translation'];
    }

    /**
     * @return Conversion
     */
    private function conv()
    {
        return $this->container['Webiik\Conversion'];
    }

    /**
     * Return host root with current scheme eg.: http://localhost
     * @return string
     */
    private function getHostRoot()
    {
        $pageURL = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $pageURL .= 's';
        }
        $pageURL .= '://' . $_SERVER['SERVER_NAME'];

        return $pageURL;
    }

    /**
     * Return web root with current scheme eg.: http://localhost/my-web-site/
     * @return string
     */
    private function getWebRoot()
    {
        $pageURL = $this->getHostRoot();
        $pageURL .= rtrim($this->getScriptDir(), '/');
        return $pageURL;
    }
}