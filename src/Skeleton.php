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
    private $lang;

    private $fallbackLangs;

    /**
     * Skeleton constructor.
     */
    public function __construct($config = [])
    {
        parent::__construct();

        $this->addParam('config', $config);

        // Add basic Skeleton services
        $this->addService('auth', function ($c) {
            return new Auth();
        });
        $this->addService('conversion', function ($c) {
            return new Conversion();
        });
        $this->addService('connection', function ($c) {
            $connection = new Connection();
            if (isset($c['config']['database']) && !isset($c['config']['database'][0])) {
                foreach($c['config']['database'] as $name => $p){
                    $connection->add($name, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
                }
            }
            return new Connection();
        });
        $this->addService('render', function ($c) {
            return new Render();
        });
        $this->addService('translation', function ($c) {
            return new Translation();
        });

        // Init Skeleton
        $this->init();
    }

    public function run()
    {
        // Load route definitions from file and if we have some routes definitions,
        // map routes that can be translated
        $routes = $this->loadRoutes($this->lang);
        if ($routes) {
            foreach ($routes as $name => $p) {
                $this->mapTranslatedRoute($name, $p);
            }
        }

        // Match URI against defined routes
        $routeInfo = $this->router()->match();

        // If there is no match, show error page
        if ($routeInfo['http_status'] == 404) $this->error(404);
        if ($routeInfo['http_status'] == 405) $this->error(405);

        // Todo: Load app and page conversions if there are some
        // Todo: Load app and page translations to Translation if there are some

        // Get handler class name
        $handler = explode(':', $routeInfo['handler']);
        $className = $handler[0];

        // Prepare args for current route handler
        $args[] = array_merge(
            $routeInfo,
            [
                'base' => $this->getWebRoot(),
                'lang' => $this->lang,
                'langs' => $this->container['config']['language'],
            ]
        );
        if (isset($this->container[$className])) {
            $args = array_merge($args, $this->container[$className]);
        }

        // Run middlewares
        $middleware = new Middleware();
        $appMiddleawares = isset($this->middlewares['app']) ? $this->middlewares['app'] : [];
        $routeMiddlewares = isset($this->middlewares[$routeInfo['id']]) ? $this->middlewares[$routeInfo['id']] : [];
        $middleware->run(array_merge(
            array_reverse($appMiddleawares),
            array_reverse($routeMiddlewares),
            [['mw' => $routeInfo['handler'], 'args' => $args]]
        ));
    }

    /**
     * Return translation for given key in current lang. If key does not exist in current
     * lang, try to load translation of that key from fallback langs. Return false if
     * key does not exist in any lang.
     */
    public function _t($key, $fallbackIndex = 0)
    {
        $trans = $this->trans();
        $t = $trans->_t($key);
        if (!$t) {
            if ($this->fallbackLangs && isset($this->fallbackLangs[$fallbackIndex])) {
                $this->loadTranslation($this->fallbackLangs[$fallbackIndex], $key);
                $fallbackIndex++;
                $t = $this->_t($key, $fallbackIndex);
            }
        }
        return $t;
    }

    /**
     * Do the following during Skeleton instantiation
     */
    private function init()
    {
        // Set app main lang (can return 404)
        $this->setLang();
        $this->trans()->setLang($this->lang);

        // Set fallback languages
        $this->setFallbackLangs($this->lang);
        if ($this->fallbackLangs) {
            $this->trans()->setFallbacks($this->lang, $this->fallbackLangs);
        }

        // Load route translations in current lang
        $this->loadTranslation($this->lang, 'routes');
    }

    /**
     * Try to find lang in URI and compare that lang with available languages.
     * If language in URI is valid lang, set that lang.
     * If there is no language in URI and dlInUri is false, set default lang.
     * If there is no language in URI and dlInUri is true return 404 error page
     */
    private function setLang()
    {
        $langs = $this->container['config']['language'];

        // Get web root URI
        $uri = str_replace($this->getScriptDir(), '', $_SERVER['REQUEST_URI']);

        // Get lang from URI
        preg_match('/^\/([\w]{2})\/|^\/([\w]{2})$/', $uri, $matches);

        // Did we find some language in URI?
        if (count($matches) > 0) {
            // Yes we do. So check if the lang is valid lang.
            foreach ($langs as $lang => $prop) {
                if ($lang == $matches[1]) break;
            }
        }

        // If we didn't find any lang, it can be still ok, if default lang doesn't need to be in URI
        // Otherwise page doesn't exist.
        if (!isset($lang) && !$this->container['config']['dlInUri']) {
            $lang = key($langs);
        } else {
            $this->error(404);
        }

        $this->lang = $lang;
    }

    /**
     * Check if there are configured some fallback languages and store info about that for further usage
     * @param $lang
     */
    private function setFallbackLangs($lang)
    {
        $langs = $this->container['config']['language'];

        if (isset($langs[$lang][1])
            && is_array($langs[$lang][1])
        ) {
            $fallbackLangs = $langs[$lang][1];
        } else {
            $fallbackLangs = false;
        }

        $this->fallbackLangs = $fallbackLangs;
    }

    /**
     * Load file with app translations. If key is specified and exists add translation for
     * that key to Translation object, otherwise add to Translation object all translations from loaded file.
     * Save memory by using keys.
     */
    private function loadTranslation($lang, $key = false)
    {
        $dir = $this->container['config']['appDir'];

        if (file_exists($dir . '/translations/app.' . $lang . '.php')) {

            $trans = $this->trans();
            $val = require $dir . '/translations/app.' . $lang . '.php';

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

            if ($val) $trans->addTrans($lang, $val, $key);
        }
    }

    /**
     * Map the route that has the translation.
     */
    private function mapTranslatedRoute($name, $p)
    {
        $uri = $this->_t('routes.' . $p['utk']);

        if ($uri) {
            $route = $this->map($p['methods'], $uri, $p['controller'], $name);
            if (isset($p['middlewares'])) {
                foreach ($p['middlewares'] as $mw => $params) {
                    $route->add($mw, $params);
                }
            }
        }
    }

    /**
     * Return route definitions from file if file exists otherwise return false
     * @param $lang
     * @return bool|mixed
     */
    private function loadRoutes($lang)
    {
        $dir = $this->container['config']['appDir'];

        if (file_exists($dir . '/routes/routes.' . $lang . '.php')) {
            $routes = require $dir . '/routes/routes.' . $lang . '.php';
        } elseif (file_exists($dir . '/routes/routes.php')) {
            $routes = require $dir . '/routes/routes.php';
        }

        return isset($routes) ? $routes : false;
    }

    /**
     * @return Connection
     */
    private function connection()
    {
        return $this->container['connection'];
    }

    /**
     * @return Translation
     */
    private function trans()
    {
        return $this->container['translation'];
    }

    /**
     * @return Conversion
     */
    private function conv()
    {
        return $this->container['conversion'];
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