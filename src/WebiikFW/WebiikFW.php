<?php

namespace Webiik;

class WebiikFW extends Webiik
{
    /**
     * List of all languages of mapped routes
     * @var array
     */
    private $mappedRoutesLang = [];

    public function __construct($config)
    {
        parent::__construct($config);

        // Add middlewares...

        // Add security headers middleware
        $this->add('Webiik\MwSecurity');

        // Add stuff to Pimple\Container via Webiik\Container...

        // Add Arr
        $this->container()->addService('Webiik\Arr', function ($c) {
            return new Arr();
        });

        // Add Cookie
        $this->container()->addService('Webiik\Cookie', function ($c) {
            $cookie = new Cookie($c['Webiik\Arr']);
            $cookie->setDomain($c['WConfig']['Cookie']['domain']);
            $cookie->setUri($c['WConfig']['Cookie']['uri']);
            $cookie->setSecure($c['WConfig']['Cookie']['secure']);
            $cookie->setHttponly($c['WConfig']['Cookie']['httpOnly']);
            return $cookie;
        });

        // Add Session
        $this->container()->addService('Webiik\Session', function ($c) {
            $sessions = new Session($c['Webiik\Arr'], $c['Webiik\Cookie']);
            $sessions->setSessionName($c['WConfig']['Session']['name']);
            $sessions->setSessionDir($c['WConfig']['Session']['dir']);
            $sessions->setSessionCookieLifetime($c['WConfig']['Session']['cookieLifetime']);
            $sessions->setSessionGcLifetime($c['WConfig']['Session']['gcLifetime']);
            return $sessions;
        });

        // Add Flash
        $this->container()->addService('Webiik\Flash', function ($c) {
            return new Flash($c['Webiik\Session'], $c['Webiik\Arr']);
        });

        // Add Token
        $this->container()->addService('Webiik\Token', function ($c) {
            return new Token();
        });

        // Add Translation
        $this->container()->addService('Webiik\WTranslation', function ($c) {
            $translation = new WTranslation($c['Webiik\Request'], $c['Webiik\Arr'], $c['WConfig']);
            $translation->activateLogWarnings($c['WConfig']['Translation']['logWarnings']);
            return $translation;
        });

        // Add Conversion
        $this->container()->addService('Webiik\Conversion', function ($c) {
            return new Conversion();
        });

        // Add Connection
        $this->container()->addService('Webiik\Connection', function ($c) {
            $connection = new Connection(!$c['WConfig']['Error']['silent']);
            foreach ($c['WConfig']['Connection'] as $name => $p) {
                $connection->add($name, $p[0], $p[1], $p[2], $p[3], $p[4], 'utf8', date('e'));
            }
            return $connection;
        });

        // Add Csrf
        $this->container()->addService('Webiik\Csrf', function ($c) {
            $csrf = new Csrf($c['Webiik\Token'], $c['Webiik\Session']);
            $csrf->setTokenName($c['WConfig']['Csrf']['tokenName']);
            return $csrf;
        });

        // Add Attempts
        $this->container()->addService('Webiik\Attempts', function ($c) {
            return new Attempts($c['Webiik\Connection']);
        });

        // Add Auth
        $this->container()->addService('Webiik\Auth', function ($c) {
            $auth = new Auth($c['Webiik\Cookie'], $c['Webiik\Session'], $c['Webiik\Token']);
            if ($c['WConfig']['Auth']['accountResolutionMode'] > 0) {
                $auth->setAuthSuffix($this->trans()->getLang());
            }
            $auth->setSessionName($c['WConfig']['Auth']['loginSessionName']);
            $auth->setAutoLogoutTime($c['WConfig']['Auth']['autoLogoutTime']);
            $auth->setPermanentCookieName($c['WConfig']['Auth']['permanentCookieName']);
            $auth->setPermanentCookieExpirationTime($c['WConfig']['Auth']['permanentCookieExpirationTime']);
            $auth->setPermanentFilesDir($c['WConfig']['Auth']['permanentLoginFilesDir']);
            $auth->setAutoDeleteExpiredPermanentRecords($c['WConfig']['Auth']['autoDeleteExpiredPermanentRecords']);
            return $auth;
        });

        // Add AuthExtended
        $this->container()->addService('Webiik\AuthExtended', function ($c) {
            $auth = new AuthExtended($c['Webiik\Cookie'], $c['Webiik\Session'], $c['Webiik\Connection'], $c['Webiik\Token'], $c['Webiik\Attempts']);
            if ($c['WConfig']['Auth']['accountResolutionMode'] > 0) {
                $auth->setAuthSuffix($this->trans()->getLang());
            }
            $auth->setSessionName($c['WConfig']['Auth']['loginSessionName']);
            $auth->setAutoLogoutTime($c['WConfig']['Auth']['autoLogoutTime']);
            $auth->setPermanentCookieName($c['WConfig']['Auth']['permanentCookieName']);
            $auth->setPermanentCookieExpirationTime($c['WConfig']['Auth']['permanentCookieExpirationTime']);
            $auth->setPermanentFilesDir($c['WConfig']['Auth']['permanentLoginFilesDir']);
            $auth->setAutoDeleteExpiredPermanentRecords($c['WConfig']['Auth']['autoDeleteExpiredPermanentRecords']);
            $auth->setWithActivation($c['WConfig']['AuthExtended']['withActivation']);
            $auth->setSalt($c['WConfig']['AuthExtended']['salt']);
            $auth->setSuffix($this->trans()->getLang());
            $auth->setAttemptsLimit($c['WConfig']['AuthExtended']['attemptsLimit'][0], $c['WConfig']['AuthExtended']['attemptsLimit'][1]);
            $auth->setConfirmationTime($c['WConfig']['AuthExtended']['confirmationTime']);
            $auth->setUserAccountResolution($c['WConfig']['Auth']['accountResolutionMode']);
            return $auth;
        });

        // Add MwAuth
        $this->container()->addService('Webiik\MwAuth', function ($c) {
            $authMwRedirect = new \Webiik\MwAuth(...WContainer::DIconstructor('Webiik\MwAuth', $this->container()));
            return $authMwRedirect;
        });

        // Add Render
        $this->container()->addService('Webiik\WRender', function ($c) {
            $render = new \Webiik\WRender($c['Webiik\WTranslation']);
            $render->addFileRenderHandler($c['WConfig']['WebiikFW']['privateDir'] . '/app/views/');
            return $render;
        });

        // Add PHPMailer service
        // Todo: Move PHPMailer out of there, it should be in boilerplate
        $this->container()->addService('PHPMailer', function ($c) {
            $mail = new \PHPMailer();
            if ($c['WConfig']['PHPMailer']['SMTP']['isSMPT']) {
                $mail->isSMTP();
                $mail->Host = $c['WConfig']['PHPMailer']['SMTP']['host'];
                $mail->Port = $c['WConfig']['PHPMailer']['SMTP']['port'];
                $mail->Timeout = $c['WConfig']['PHPMailer']['SMTP']['timeout'];
                $mail->SMTPSecure = $c['WConfig']['PHPMailer']['SMTP']['SMTPSecure'];
                $mail->SMTPOptions = $c['WConfig']['PHPMailer']['SMTP']['SMTPOptions'];

                if ($c['WConfig']['PHPMailer']['SMTP']['SMTPAuth']) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $c['WConfig']['PHPMailer']['SMTP']['SMTPAuthUserName'];
                    $mail->Password = $c['WConfig']['PHPMailer']['SMTP']['SMTPAuthPswd'];
                }
            }
            $mail->setFrom($c['WConfig']['PHPMailer']['fromEmail'], $c['WConfig']['PHPMailer']['fromName']);
            return $mail;
        });

        // Add function to add PHPMailer handler to LogHandlerEmail
        $this->container()->addFunction('getCustomEmailLogHandler', function () {

            $sendMailHandler = function ($from, $to, $subject, $message) {
                $this->phpMailer()->isHTML();
                $this->phpMailer()->addAddress($to);
                $this->phpMailer()->Subject = $subject;
                $this->phpMailer()->Body = $message;
                $this->phpMailer()->send();
            };

            return $sendMailHandler;
        });

        // Init translations
        $this->trans();

        // Set time zone according to current lang
        date_default_timezone_set($config['Translation']['languages'][$this->trans()->getLang()][0]);

        // Set internal encoding
        mb_internal_encoding('utf-8');
    }

    public function run()
    {
        // Add loggers
        $this->error()->setLogger($this->c['getLogger']('error', Log::$ERROR, $this->c['getCustomEmailLogHandler']()));
        $this->router()->setLogger($this->c['getLogger']('router', Log::$WARNING, $this->c['getCustomEmailLogHandler']()));
        $this->trans()->setLogger($this->c['getLogger']('translation', Log::$WARNING, $this->c['getCustomEmailLogHandler']()));

        // Load route translations for all langs
        $langs = null;
        if (isset($this->c['WConfig']['Translation']['languages'])) {
            $langs = array_keys($this->c['WConfig']['Translation']['languages']);
        }
        $this->trans()->loadTranslations('_app', '', false, 'routes', $langs);

        // Map routes for current lang and fallbacks routes of missing current lang routes
        $lang = $this->trans()->getLang();
        $this->mapRoutes($lang);

        // Match route
        $routeInfo = $this->router()->match();

        // Handle errors
        $httpStatus = $this->router()->getStatus();
        if ($httpStatus == 404 || $httpStatus == 405) $this->handleError($httpStatus, $routeInfo['handler']);

        // Store route info into WContainer to make it easily accessible without injection whole WRouter
        $this->container()->addParam('routeInfo', $routeInfo);

        // Todo: Consider to move loading of translations, formats and conversions to standalone middleware(s).
        // Load app and current page translations in to Translation
        $this->trans()->loadTranslations('_app', '', true, false, $lang);
        $this->trans()->loadTranslations($routeInfo['name'], '', true, false, $lang);

        // Load translation formats in to Translation
        $this->loadFormats($lang);

        // Load app and page conversions in to Conversion (same for languages)
        $this->loadConversions('_app');
        $this->loadConversions($routeInfo['name']);

        // Map rest of routes with empty controllers.
        // Webiik needs this step to provide getUriFor() for every route in every lang.
        $this->mapRoutesEmptyTranslated();

        // Run middlewares and route controller
        $this->mw()->run($routeInfo);
    }

    /**
     * @return WTranslation
     */
    public function trans()
    {
        return $this->c['Webiik\WTranslation'];
    }

    /**
     * @return Conversion
     */
    public function conv()
    {
        return $this->c['Webiik\Conversion'];
    }

    /**
     * @return Arr
     */
    public function arr()
    {
        return $this->c['Webiik\Arr'];
    }

    /**
     * @return \PHPMailer
     */
    public function phpMailer()
    {
        return $this->c['PHPMailer'];
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
        $file = $this->config()['WebiikFW']['privateDir'] . '/app/routes/routes.' . $lang . '.php';
        if (file_exists($file)) {
            $routes = require $file;
        } else {
            $file = $this->config()['WebiikFW']['privateDir'] . '/app/routes/routes.php';
            $routes = require $file;
        }

        // Get route translations

        // Store current lang
        $translationLang = $this->trans()->getLang();

        // Get route translations for current lang
        $this->trans()->setLang($lang);
        $routeTranslations = $this->trans()->_t('routes');
        if (!is_array($routeTranslations)) $routeTranslations = [];

        // Get route translations for current lang fallbacks
        $fl = $this->trans()->getFallbackLangs($lang);
        foreach ($fl as $flLang) {
            $this->trans()->setLang($flLang);
            $fallbackRoutes = $this->trans()->_t('routes');
            if (is_array($fallbackRoutes)) {
                foreach ($fallbackRoutes as $name => $uri) {
                    if (!isset($routeTranslations[$name])) {
                        $routeTranslations[$name] = $uri;
                    }
                }
            }
        }

        // Set back translation to current language
        $this->trans()->setLang($translationLang);

        // Store info about that we already tried to map routes in this $lang
        $this->mappedRoutesLang[] = $lang;

        // Now iterate route definitions and map only routes that have translation
        $this->router()->setLang($lang);

        foreach ($routes as $name => $p) {

            if (isset($routeTranslations[$name])) {

                $uri = '/' . trim($routeTranslations[$name], '/');

                if (!$empty) {

                    // Standard route mapping
                    $route = $this->router()->map($p['methods'], $uri, $p['controller'], $name);
                    if (isset($p['middlewares'])) {
                        foreach ($p['middlewares'] as $mw => $params) {
                            $route->add($mw, $params);
                        }
                    }
                } else {

                    // Empty route mapping just for translation purpose
                    $this->router()->map(['GET'], $uri, '', $name);
                }
            }
        }

        // Set back router language to current language
        $this->router()->setLang($translationLang);
    }

    /**
     * Map empty routes for all other languages than current lang.
     * Empty means routes without controllers and only with GET method.
     */
    private function mapRoutesEmptyTranslated()
    {
        $currentLang = $this->trans()->getLang();

        foreach ($this->config()['Translation']['languages'] as $lang => $prop) {

            // Iterate all langs except current lang, because routes for current lang are already loaded
            if ($lang != $currentLang) {

                // Load translations for routes we did not map before
                if (!in_array($lang, $this->mappedRoutesLang)) {
                    $this->mapRoutes($lang, true);
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
        $dir = $this->config()['WebiikFW']['privateDir'] . '/app/translations/conversions';

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
        $dir = $this->config()['WebiikFW']['privateDir'] . '/app/translations/formats';

        if (file_exists($dir . '/' . $lang . '.php')) {

            $arr = require $dir . '/' . $lang . '.php';

            foreach ($arr as $type => $formats) {

                if ($type == 'date' || $type == 'time' || $type == 'number' || $type == 'currency') {
                    foreach ($formats as $name => $pattern) {
                        if ($type == 'date') $this->trans()->setDateFormat($lang, $name, $pattern);
                        if ($type == 'time') $this->trans()->setTimeFormat($lang, $name, $pattern);
                        if ($type == 'number') $this->trans()->setNumberFormat($lang, $name, $pattern);
                        if ($type == 'currency') $this->trans()->setCurrencyFormat($lang, $name, $pattern);
                    }
                }

                if ($type == 'monthsLong') $this->trans()->setLongMonthNamesTrans($formats, $lang);
                if ($type == 'monthsShort') $this->trans()->setShortMonthNamesTrans($formats, $lang);
                if ($type == 'daysLong') $this->trans()->setLongDayNamesTrans($formats, $lang);
                if ($type == 'daysShort') $this->trans()->setShortDayNamesTrans($formats, $lang);
            }
        }
    }
}