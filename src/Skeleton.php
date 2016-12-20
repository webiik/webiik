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
     * Language list of all mapped routes
     * @var array
     */
    private $mappedRoutesLang = [];

    /**
     * Skeleton constructor.
     */
    public function __construct($config)
    {
        parent::__construct();

        // Add middlewares
        $this->add('Webiik\SecurityMw');

        // Add params shared inside app
        $this->addParam('config', $config);
        $this->addParam('HOST', $this->getHostRoot());
        $this->addParam('ROOT', $this->getWebRoot());

        // Add basic app services

        // Add Array functions
        $this->addService('Webiik\Arr', function ($c) {
            return new Arr();
        });

        // Add Sessions
        $this->addService('Webiik\Sessions', function ($c) {
            $sessions = new Sessions($c['Webiik\Arr']);
            $sessions->setSessionName($c['config']['sessions']['name']);
            $sessions->setSessionDir($c['config']['sessions']['dir']);
            $sessions->setSessionSystemLifetime($c['config']['sessions']['lifetime']);
            $sessions->setDomain($c['config']['cookies']['domain']);
            $sessions->setUri($c['config']['cookies']['uri']);
            $sessions->setSecure($c['config']['cookies']['secure']);
            $sessions->setHttponly($c['config']['cookies']['httpOnly']);
            return $sessions;
        });

        // Add Flash messages
        $this->addService('Webiik\Flash', function ($c) {
            return new Flash($c['Webiik\Sessions'], $c['Webiik\Arr']);
        });

        // Add Token
        $this->addService('Webiik\Token', function ($c) {
            return new Token();
        });

        // Add Translation
        $this->addService('Webiik\Translation', function ($c) {
            return new Translation();
        });

        // Add Conversion
        $this->addService('Webiik\Conversion', function ($c) {
            return new Conversion();
        });

        // Add Filesystem
        $this->addService('Webiik\Filesystem', function ($c) {
            return new Filesystem();
        });

        // Add Connection
        $this->addService('Webiik\Connection', function ($c) {
            $connection = new Connection($c['config']['error']['debug']);
            foreach ($c['config']['connection'] as $name => $p) {
                $connection->add($name, $p[0], $p[1], $p[2], $p[3], $p[4], 'utf8', date('e'));
            }
            return $connection;
        });

        // Add Csrf
        $this->addService('Webiik\Csrf', function ($c) {
            $csrf = new Csrf($c['Webiik\Token'], $c['Webiik\Sessions']);
            $csrf->setTokenName($c['config']['csrf']['tokenName']);
            return $csrf;
        });

        // Add Attempts
        $this->addService('Webiik\Attempts', function ($c) {
            return new Attempts($c['Webiik\Connection']);
        });

        // Add Auth
        $this->addService('Webiik\Auth', function ($c) {
            $auth = new Auth($c['Webiik\Sessions'], $c['Webiik\Connection'], $c['Webiik\Token'], $c['Webiik\Attempts']);
            $auth->confCookieName($c['config']['auth']['permanentLoginCookieName']);
            $auth->confWithActivation($c['config']['auth']['withActivation']);
            return $auth;
        });

        // Add Auth middleware
        $this->addService('Webiik\AuthMw', function ($c) {
            $authMw = new AuthMw($c['Webiik\Auth'], $c['Webiik\Router'], $c['Webiik\Flash'], $c['Webiik\Translation']);
            $authMw->confLoginRouteName($c['config']['authMw']['loginRouteName']);
            return $authMw;
        });

        // Set app main lang (can return 404)
        $this->setLang();
        $this->trans()->setLang($this->lang);

        // Set fallback languages
        $this->setFallbackLangs();

        // Set time zone according to current lang
        date_default_timezone_set($config['skeleton']['languages'][$this->lang][0]);

        // Set internal encoding
        mb_internal_encoding('utf-8');
    }

    public function run()
    {
        // Load translations for current lang with all fallback route translations and fallbacks of missing translations
        $this->loadTranslations($this->lang, '_app', false, 'routes');

        // Map routes for current lang and fallbacks routes of missing current lang routes
        $this->mapRoutes($this->lang);

        // Match URI against mapped routes
        $routeInfo = $this->router()->match();

        // If there is no match, show error page and exit
        if ($routeInfo['http_status'] == 404) $this->error(404);
        if ($routeInfo['http_status'] == 405) $this->error(405);

        // Store route info into container
        $this->addParam('routeInfo', $routeInfo);

        // Load app and current page translations in to Translation
        $this->loadTranslations($this->lang, $routeInfo['name']);

        // Load translation formats in to Translation
        $this->loadFormats($this->lang);

        // Load app and page conversions in to Conversion (same for languages)
        $this->loadConversions('_app');
        $this->loadConversions($routeInfo['name']);

        // Map rest of routes with empty controllers.
        // Webiik needs this step to provide getUriFor() for every route in every lang.
        $this->mapRoutesEmptyTranslated();

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
     * Try to find lang in URI and compare that lang with available languages.
     * If language in URI is valid lang, set that lang as current lang.
     * If there is no language in URI and dlInUri is false, use default lang as current lang.
     * If there is no language in URI and dlInUri is true return 404 error page.
     */
    private function setLang()
    {
        $langs = $this->container['config']['skeleton']['languages'];

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
        if (!isset($lang) && !$this->container['config']['skeleton']['dlInUri']) {
            $lang = key($langs);
        } elseif (!isset($lang) && !$this->container['config']['skeleton']['dlInUri']) {
            $this->error(404);
        }

        $this->lang = $lang;
    }

    /**
     * Set fallback language(s) for all available languages
     */
    private function setFallbackLangs()
    {
        $langs = $this->container['config']['skeleton']['languages'];
        foreach ($langs as $lang => $p) {
            if (isset($p[1][0])) {
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
        $langs = $this->container['config']['skeleton']['languages'];

        if (isset($langs[$lang][1]) && is_array($langs[$lang][1])) {
            $fallbackLangs = $langs[$lang][1];
        } else {
            $fallbackLangs = [];
        }

        return $fallbackLangs;
    }

    /**
     * Route definitions does not provide fallbacks, otherwise it would be messy.
     * You can define routes for specific language in routes.{lang}.php or for all languages in routes.php
     * If route definition has no valid translation then route will not be mapped!
     * @param $lang
     * @param bool $empty
     */
    private function mapRoutes($lang, $empty = false)
    {
        // Get route definitions
        $routes = [];
        $file = $this->container['config']['skeleton']['privateDir'] . '/app/routes/routes.' . $lang . '.php';
        if (file_exists($file)) {
            $routes = require $file;
        } else {
            $file = $this->container['config']['skeleton']['privateDir'] . '/app/routes/routes.php';
            $routes = require $file;
        }

        // Get route translations

        $trans = $this->trans();

        // Get route translations for current lang
        $trans->setLang($lang);
        $routeTranslations = $trans->_t('routes');
        if (!is_array($routeTranslations)) $routeTranslations = [];

        // Get route translations for current lang fallbacks
        $fl = $this->getFallbackLangs($lang);
        foreach ($fl as $flLang) {
            $trans->setLang($flLang);
            $fallbackRoutes = $trans->_t('routes');
            if (is_array($fallbackRoutes)) {
                foreach ($fallbackRoutes as $name => $uri) {
                    if (!isset($routeTranslations[$name])) {
                        $routeTranslations[$name] = $uri;
                    }
                }
            }
        }
        $trans->setLang($this->lang);

        // Store info about that we already tried to map routes in this $lang
        $this->mappedRoutesLang[] = $lang;

        // Now iterate route definitions and map only routes that have translation

        $this->router()->setLang($lang);

        foreach ($routes as $name => $p) {

            if (isset($routeTranslations[$name])) {
                $uri = '/' . trim($routeTranslations[$name], '/');

                if (!$empty) {

                    // Standard route mapping
                    $route = $this->map($p['methods'], $uri, $p['controller'], $name);
                    if (isset($p['middlewares'])) {
                        foreach ($p['middlewares'] as $mw => $params) {
                            $route->add($mw, $params);
                        }
                    }
                } else {

                    // Empty route mapping just for translation purpose
                    $this->map(['GET'], $uri, '', $name);
                }
            }
        }

        $this->router()->setLang($this->lang);
    }

    /**
     * Map empty routes for all other languages than current lang.
     * Empty means routes without controllers and only with GET method.
     */
    private function mapRoutesEmptyTranslated()
    {
        foreach ($this->container['config']['skeleton']['languages'] as $lang => $prop) {

            // Iterate all langs except current lang, because routes for current lang are already loaded
            if ($lang != $this->lang) {

                // Load translations for routes we did not map before
                if (!in_array($lang, $this->mappedRoutesLang)) {
                    $this->loadTranslations($lang, '_app', false, 'routes');
                }

                $this->mapRoutes($lang, true);
            }
        }
    }

    /**
     * Load translation from file into Translation service
     * @param $lang
     * @param $fileName
     * @param bool $addOnlyDiffTranslation - Add only missing translations in current lang
     * @param bool $key - When $addOnlyDiffTranslation is false, you can specify what part of translation will be added
     */
    private function loadTranslations($lang, $fileName, $addOnlyDiffTranslation = true, $key = false)
    {
        $file = $this->container['config']['skeleton']['privateDir'] . '/app/translations/' . $fileName . '.' . $lang . '.php';

        // Add translation for current lang
        if (file_exists($file)) {

            $translation = require $file;
            $this->trans()->addTrans($lang, $translation);

        }

        // Get fallback languages and iterate them
        $fl = $this->getFallbackLangs($this->lang);

        foreach ($fl as $flLang) {

            $file = $this->container['config']['skeleton']['privateDir'] . '/app/translations/' . $fileName . '.' . $flLang . '.php';

            // Load fallback translations
            if (file_exists($file)) {

                // Get translation for current lang
                $currentLangTranslation = $this->trans()->_tAll($this->lang);

                // Get translation for iterated fallback
                $translation = require $file;

                if (!$addOnlyDiffTranslation) {

                    // Add translation
                    if ($key) {
                        $this->trans()->addTrans($flLang, $translation[$key], $key);
                    } else {
                        $this->trans()->addTrans($flLang, $translation);
                    }
                }

                // Find keys that missing in current lang translation
                $missingTranslations = $this->arr()->diffMultiABKeys($translation, $currentLangTranslation);

                // Add this missing translation
                foreach ($missingTranslations as $key => $val) {
                    $this->trans()->addTrans($flLang, $val, $key);
                }
            }
        }
    }

    /**
     * If conversion file exists, add all conversions from that file to Conversion
     * @param string $file
     */
    private function loadConversions($file)
    {
        $dir = $this->container['config']['skeleton']['privateDir'] . '/app/translations/conversions';

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
        $dir = $this->container['config']['skeleton']['privateDir'] . '/app/translations/formats';

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
     * @return Arr
     */
    private function arr()
    {
        return $this->container['Webiik\Arr'];
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

    private function getWebRoot()
    {
        $pageURL = $this->getHostRoot();
        $pageURL .= rtrim($this->getScriptDir(), '/');
        return $pageURL;
    }
}