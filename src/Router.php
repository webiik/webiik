<?php
namespace Webiik;

/**
 * Class Router
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/router
 * @license     MIT
 */
class Router
{
    /** @var string Lang of mapped routes */
    private $lang = 'en';

    /** @var array Info about requested route */
    public $routeInfo = [];

    /**
     * Router configuration
     *
     * Options:
     * basePath - Base directory of your web app relative to host root
     * duplicatesRecognition - Set to true just for development. Turning off saves time and memory
     * slashRedirect - http://googlewebmastercentral.blogspot.cz/2010/04/to-slash-or-not-to-slash.html
     * defaultLang - default language
     * defaultLangInUri - Show default lang in URI? If true home page in default language will be eg.: webiik.com/en/
     * @var array
     */
    private $config = [
        'basePath' => '',
        'duplicatesRecognition' => false,
        'slashRedirect' => true,
        'defaultLang' => 'en',
        'defaultLangInUri' => false,
    ];

    /**
     * Check your regex patterns with http://regexr.com
     * @var array of Conditions
     */
    private $conditionTypes = [
        'az' => '^[a-zA-Z]+$',
        'i' => '^[0-9]+$',
    ];

    /** @var array All routes names, uris, available methods */
    private $routes = [];

    /** @var array Just uri masks of routes for fast duplicates recognition */
    private $routeUris = [];

    /** @var array Just names of routes for fast duplicates recognition */
    private $routeNames = [];

    /**
     * Set language of mapped routes
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * Set default language
     * @param $lang
     */
    public function setDefaultLang($lang)
    {
        $this->config['defaultLang'] = $lang;
    }

    /**
     * Set base directory of your web app relative to web root
     * @param $basePath
     */
    public function base($basePath)
    {
        if ($basePath == '/') {
            $basePath = '';
        } else {
            $basePath = '/' . trim($basePath, '/');
        }

        $this->config['basePath'] = $basePath;
    }

    /**
     * Add route to $this->routes
     * @param array $methods HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS
     * @param string $route
     * @param string $handler
     * @param boolean|string $name
     * @return int
     * @throws \Exception
     */
    public function map($methods = [], $route, $handler, $name = false)
    {
        if ($this->config['duplicatesRecognition'] == true) {
            if (isset($this->routeUris[$this->lang][$route])) {
                throw new \Exception('Can not redeclare same route again {' . $route . '}');
            }
            $this->routeUris[$this->lang][$route] = true;

            if ($name) {
                if (isset($this->routeNames[$this->lang][$name])) {
                    throw new \Exception('Can not redeclare route name {' . $name . '}');
                }
                $this->routeNames[$this->lang][$name] = true;
            }
        }

        $route = $this->getUriLangPrefix($this->lang) . $route;

        $route = [
            'methods' => $methods,
            'uri' => $route,
            'handler' => $handler,
            'name' => $name,
        ];

        $this->routes[$this->lang][] = $route;

        end($this->routes[$this->lang]);
        return key($this->routes[$this->lang]);
    }

    /**
     * Remove route from $this->routes
     * @param string $route
     */
    public function unmap($route, $lang)
    {
        unset($this->routes[$lang][$route]);
    }

    /**
     * Remove all routes from $this->routes
     */
    public function unmapAll()
    {
        $this->routes = [];
    }

    /**
     * Add config lines. It uses array_merge so keys can be overwritten.
     * @param array $keyValueArray The key is the name and the value is the regex.
     */
    public function setConfig($keyValueArray)
    {
        $this->config = array_merge($this->config, $keyValueArray);
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     * @param array $keyValueArray The key is the name and the value is the regex.
     */
    public function setConditionTypes($keyValueArray)
    {
        $this->conditionTypes = array_merge($this->conditionTypes, $keyValueArray);
    }

    /**
     * Return a regex for the given name or false if the name does not exists.
     * @param $condName
     * @return string|bool
     */
    private function getConditionRegex($condName)
    {
        if (isset($this->conditionTypes[$condName])) {
            return $this->conditionTypes[$condName];
        }
        return false;
    }

    /**
     * Get URI lang prefix
     * URIs in other language than default will have lang prefix according
     * to the following pattern /{lang}/{uri} eg.: /cs/kontakt
     * @return string
     */
    private function getUriLangPrefix($lang)
    {
        $defaultLang = $this->config['defaultLang'];
        if ($lang != $defaultLang || $this->config['defaultLangInUri']) {
            $langPrefix = '/' . $lang;
        } else {
            $langPrefix = '';
        }

        return $langPrefix;
    }

    /**
     * Redirect the URL without or with many slashes at the and to the URL with one slash at the end
     * http://googlewebmastercentral.blogspot.cz/2010/04/to-slash-or-not-to-slash.html
     */
    private function slashRedirect()
    {
        if ($this->config['slashRedirect'] == true) {
            if (count($_GET) == 0 && (substr($_SERVER['REQUEST_URI'], -1) != '/' || substr($_SERVER['REQUEST_URI'], -2) == '//')) {
                $redirectUrl = rtrim($_SERVER['REQUEST_URI'], '/') . '/';
                header('HTTP/1.1 301 Moved Permanently');
                header('Location:' . $redirectUrl);
                exit();
            }
        }
    }

    /**
     * Return the URI for a named route
     * @param string $routeName
     * @param bool|string $lang
     * @param array $params
     * @return string|boolean
     * @throws \Exception
     */
    public function getUriFor($routeName, $lang = false, $params = [])
    {
        if (!$lang) $lang = $this->lang;

        if (isset($this->routes[$lang])) {

            foreach ($this->routes[$lang] as $route) {

                if ($route['name'] == $routeName) {

                    $routeParams = false;
                    $uri = '/';

                    if ($route['uri'] != '/') {

                        // Get param names, conditions and its count
                        $routeUriParts = explode('/', trim($route['uri'], '/'));
                        $reqParamsCount = $wildParamsCount = $optParamsCount = 0;

                        $i = 0;
                        foreach ($routeUriParts as $routeUriPart) {

                            if ($routeUriPart[0] == ':') {
                                // Required parameter
                                $reqParamsCount++;
                            } else if ($routeUriPart[0] == '*') {
                                // Wildcard parameter
                                $wildParamsCount++;
                            } else if ($routeUriPart[0] == '?') {
                                // Optional parameter
                                $optParamsCount++;
                            } else {
                                $uri .= $routeUriPart . '/';
                            }

                            // Store param and condition name
                            if ($routeUriPart[0] == ':' || $routeUriPart[0] == '*' || $routeUriPart[0] == '?') {
                                $paramNameCond = explode('.', $routeUriPart);
                                $routeParams[$i]['paramName'] = substr($paramNameCond[0], 1);
                                if (isset($paramNameCond[1])) $routeParams[$i]['condName'] = $paramNameCond[1];
                                $i++;
                            }
                        }

                        // Check if we have got right count of route parameters
                        $paramsCount = count($params);
                        if ((($paramsCount >= $reqParamsCount) && ($paramsCount <= $reqParamsCount + $optParamsCount)) || (($paramsCount >= $reqParamsCount) && $wildParamsCount > 0)) {

                            // Check if params match conditions if any
                            $i = 0;
                            foreach ($params as $givenParam) {

                                if (!isset($routeParams[$i])) {
                                    $i--;
                                }

                                if (isset($routeParams[$i]['condName'])) {
                                    $conditionRegex = $this->getConditionRegex($routeParams[$i]['condName']);
                                    if (!preg_match('/' . $conditionRegex . '/', $givenParam)) {
                                        $msg = [
                                            'method' => 'UrlFor',
                                            'param' => $givenParam,
                                            'regex' => $conditionRegex,
                                            'msg' => 'UrlFor() - parameter {' . $givenParam . '} must match the following regex {/' . $conditionRegex . '/}.',
                                        ];
                                        Log::log('router', $msg, true);
                                        return false;
                                    }

                                }

                                $uri .= $givenParam . '/';
                                $i++;
                            }
                        } else {
                            $msg = [
                                'method' => 'UrlFor',
                                'routeName' => $routeName,
                                'requieredParamsCount' => $reqParamsCount,
                                'optionalParamsCount' => $optParamsCount,
                                'wildcardParamsCount' => $wildParamsCount,
                                'givenParamsCount' => $paramsCount,
                                'msg' => 'UrlFor() - route with name {' . $routeName . '} has {' . $reqParamsCount . '} required, {' . $optParamsCount . '} optional and {' . $wildParamsCount . '} wildcard parameter(s), but {' . $paramsCount . '} parameters was given.',
                            ];
                            Log::log('router', $msg, true);
                            return false;
                        }
                    }
                    return $uri;
                }
            }
        }

        $msg = [
            'method' => 'UrlFor',
            'routeName' => $routeName,
            'lang' => $lang,
            'msg' => 'UrlFor() - route with name {' . $routeName . '} does not exist in {' . $lang . '} lang.',
        ];
        Log::log('router', $msg, true);
        return false;
    }

    /**
     * Return the URI with base path for a named route
     * @param string $routeName
     * @param bool|string $lang
     * @param array $params
     * @return string|boolean
     * @throws \Exception
     */
    public function getUrlFor($routeName, $lang = false, $params = [])
    {
        $uri = $this->getUriFor($routeName, $lang, $params);

        if ($uri) {
            return $this->config['basePath'] . $uri;
        }

        return $uri;
    }

    /**
     * Match a REQUEST_URI against stored $routes
     */
    public function match()
    {
        $this->slashRedirect();

        $this->routeInfo['http_status'] = 404;

        // Force request_order to be GP
        // http://www.mail-archive.com/internals@lists.php.net/msg33119.html
        $_REQUEST = array_merge($_GET, $_POST);

        // Get request uri without query string
        $request_uri = $_SERVER['REQUEST_URI'];
        if (($strpos = strpos($request_uri, '?')) !== false) {
            $request_uri = substr($request_uri, 0, $strpos);
        }

        // Strip base path from request url
        $request_uri = substr($request_uri, strlen($this->config['basePath']));

        foreach ($this->routes[$this->lang] as $routeId => $route) {

            $routeRegexMask = '';
            $routeNoRegexPart = '';

            $routeParams = false;

            // Get $route['uri'] parts
            if ($route['uri'] != '/') {

                $routeUriParts = explode('/', trim($route['uri'], '/'));

                $i = 0;
                foreach ($routeUriParts as $routeUriPart) {

                    if ($routeUriPart[0] == ':') {
                        // Required parameter
                        $routeRegexMask .= '[a-zA-Z0-9\-]+\/';
                    } else if ($routeUriPart[0] == '*') {
                        // Wildcard parameter
                        $routeRegexMask .= '[a-zA-Z0-9\/\-]+';
                    } else if ($routeUriPart[0] == '?') {
                        // Optional parameter
                        $routeRegexMask .= '([a-zA-Z0-9\-]+)?\/';
                    } else {
                        $routeRegexMask .= $routeUriPart . '\/';
                        $routeNoRegexPart .= $routeUriPart . '/';
                    }

                    // Store param and condition name
                    if ($routeUriPart[0] == ':' || $routeUriPart[0] == '*' || $routeUriPart[0] == '?') {
                        $paramNameCond = explode('.', $routeUriPart);
                        $routeParams[$i]['paramName'] = substr($paramNameCond[0], 1);
                        if (isset($paramNameCond[1])) $routeParams[$i]['condName'] = $paramNameCond[1];
                        $i++;
                    }
                }

                // Complete route regex mask
                $routeRegexMask = '\/' . $routeRegexMask;
            } else {
                $routeRegexMask = '\/';
            }

            // Finalize regex mask
            if (substr($routeRegexMask, -2) == '\/') {
                $routeRegexMask = substr($routeRegexMask, 0, -2) . '(\/?)';
            }

            // Compare REQUEST_URI with $route regex mask, proceed when match
            if (preg_match('/^' . $routeRegexMask . '$/', $request_uri, $requestUri)) {

                // Check if this route is available in current REQUEST_METHOD
                $methodMatch = false;
                foreach ($route['methods'] as $httpMethod) {
                    if ($_SERVER['REQUEST_METHOD'] == strtoupper($httpMethod)) {
                        $methodMatch = true;
                        break;
                    }
                }

                // Route is not available in current REQUEST_METHOD, continue to next route
                if (!$methodMatch) {
                    $this->routeInfo['http_status'] = 405;
                    break;
                }

                // Add route name, available http methods and params with conditions to routeInfo
                $this->routeInfo['http_status'] = 200;
                $this->routeInfo['methods'] = $route['methods'];
                $this->routeInfo['id'] = $routeId;
                $this->routeInfo['name'] = $route['name'];
                $this->routeInfo['lang'] = $this->lang;

                // If we have some params, associate values from request uri with params names
                // and check if values of params match params conditions
                if ($routeParams) {
                    // Get parameters values from REQUEST_URI, iterate them and match them with params we
                    // have got from $routeUriRegexParts. If we have more params values from REQUEST_URI
                    // than $routeUriRegexParts that means that parameter is wildcard.
                    if ($routeNoRegexPart) {
                        $requestUriParts = explode('/', trim(str_replace('/' . $routeNoRegexPart, '', $requestUri[0]), '/'));
                    } else {
                        $requestUriParts = explode('/', trim($requestUri[0], '/'));
                    }

                    $i = 0;
                    foreach ($requestUriParts as $paramValue) {

                        if (isset($routeParams[$i])) {
                            // Add single value to param
                            $routeParams[$i]['value'] = $paramValue;
                            $i++;
                        } else {
                            // Add value as array to param
                            if (!isset($wildcard)) {
                                $lastAddedValue = $routeParams[$i - 1]['value'];
                                $routeParams[$i - 1]['value'] = array();
                                $routeParams[$i - 1]['value'][] = $lastAddedValue;
                                $wildcard = true;
                            }
                            $routeParams[$i - 1]['value'][] = $paramValue;
                        }

                        // Check if value must to pass some param condition
                        if (isset($routeParams[$i - 1]['condName'])) {
                            $conditionRegex = $this->getConditionRegex($routeParams[$i - 1]['condName']);
                            if ($conditionRegex) {
                                if (!preg_match('/' . $conditionRegex . '/', $paramValue)) {
                                    // URL exists but parameter {' . $paramValue . '} do not match the regex {/' . $conditionRegex . '/}
                                    $this->routeInfo['http_status'] = 404;
                                }
                            }
                        }
                    }
                }
                // Add params with conditions and values to routeInfo
                $this->routeInfo['params'] = $routeParams;

                // Add route handler to routeInfo
                $this->routeInfo['handler'] = $route['handler'];
            }
        }

        return $this->routeInfo;
    }
}