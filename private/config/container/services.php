<?php
return [
    // https://www.webiik.com/arr/
    'Webiik\Arr\Arr' => function () {
        return new \Webiik\Arr\Arr();
    },

    // https://www.webiik.com/flash/
    'Webiik\Flash\Flash' => function (\Webiik\Container\Container $c) {
        $flash = new \Webiik\Flash\Flash($c->get('Webiik\Session\Session'));
        $flash->setLang(WEBIIK_LANG);
        return $flash;
    },

    // https://www.webiik.com/mail/
    'Webiik\Mail\Mail' => function (\Webiik\Container\Container $c) {
        $mail = new \Webiik\Mail\Mail();

        // Add PHPMailer
        // https://github.com/PHPMailer/PHPMailer
        $mail->setMailer(function () use (&$c) {
            $phpMailerConfig = $c->get('wsConfig')->get('services')['Mail']['PHPMailer'];
            $phpMailer = new \PHPMailer\PHPMailer\PHPMailer();
            if ($phpMailerConfig['isSMTP']) {
                $phpMailer->isSMTP();
                $phpMailer->Host = $phpMailerConfig['SMTP']['host'];
                $phpMailer->Port = $phpMailerConfig['SMTP']['port'];
                $phpMailer->Timeout = $phpMailerConfig['SMTP']['timeout'];
                $phpMailer->SMTPSecure = $phpMailerConfig['SMTP']['SMTPSecure'];
                $phpMailer->SMTPOptions = $phpMailerConfig['SMTP']['SMTPOptions'];
                if ($phpMailerConfig['SMTP']['SMTPAuth']) {
                    $phpMailer->SMTPAuth = true;
                    $phpMailer->Username = $phpMailerConfig['SMTP']['SMTPAuthUserName'];
                    $phpMailer->Password = $phpMailerConfig['SMTP']['SMTPAuthPswd'];
                }
            }
            return new \Webiik\Mail\Mailer\PHPMailer(new \PHPMailer\PHPMailer\PHPMailer());
        });

        return $mail;
    },

    // https://www.webiik.com/log/
    'Webiik\Log\Log' => function (\Webiik\Container\Container $c) {
        $log = new \Webiik\Log\Log();

        // Configure silent mode
        $log->setSilent(!WEBIIK_DEBUG);

        // Add ErrorLogger for messages in group error
        $log->addLogger(function () {
            $logger = new \Webiik\Log\Logger\ErrorLogger();
            $logger->setMessageType(3);
            $logger->setDestination(WEBIIK_BASE_DIR . '/tmp/logs/error.log');
            return $logger;
        })->setGroup('error');

        // Add MailLogger for messages in group error
        // Use MailLogger only in silent mode and when MailLogger is active
        if ($c->get('wsConfig')->get('services')['Error']['silent'] && $c->get('wsConfig')->get('services')['Log']['MailLogger']['active']) {
            $log->addLogger(function () use (&$c) {
                $logger = new \Webiik\Log\Logger\MailLogger();
                $logger->setTmpDir(WEBIIK_BASE_DIR . '/tmp/logs/sent');
                $logger->setDelay($c->get('wsConfig')->get('services')['Log']['MailLogger']['error']['delay']);
                $logger->setSubject($c->get('wsConfig')->get('services')['Log']['MailLogger']['error']['subject']);
                $logger->setFrom($c->get('wsConfig')->get('services')['Log']['MailLogger']['from']);
                $logger->setTo($c->get('wsConfig')->get('services')['Log']['MailLogger']['error']['to']);
                $logger->setMailService(function (string $from, string $to, string $subject, string $message) use (&$c
                ) {
                    /** @var \Webiik\Mail\Mail $webiikMail */
                    $webiikMail = $c->get('\Webiik\Mail\Mail');
                    $webiikMailMessage = $webiikMail->createMessage();
                    $webiikMailMessage->setFrom($from);
                    $webiikMailMessage->addTo($to);
                    $webiikMailMessage->setSubject($subject);
                    $webiikMailMessage->setBody($message);
                    $webiikMail->send([$webiikMailMessage]);
                });
                return $logger;
            })->setGroup('error');
        }

        return $log;
    },

    // https://www.webiik.com/error/
    'Webiik\Error\Error' => function (\Webiik\Container\Container $c) {
        $error = new \Webiik\Error\Error();

        // Configure silent mode
        $error->setSilent(!WEBIIK_DEBUG);
        $error->setSilentPageContent('Meow, something went wrong!');

        // Write log messages using the \Webiik\Log\Log
        $error->setLogService(function ($level, $message, $data) use (&$c) {
            /** @var \Webiik\Log\Log $log */
            $log = $c->get('Webiik\Log\Log');
            $log->log($level, $message)->setData($data)->setGroup('error');
            $log->write();
        });

        return $error;
    },

    // https://www.webiik.com/translation/
    'Webiik\Translation\Translation' => function (\Webiik\Container\Container $c) {
        $translation = new \Webiik\Translation\Translation($c->get('Webiik\Arr\Arr'));
        $translation->setLang(WEBIIK_LANG);

        // Add injections for custom parsers
        $translation->inject('Route', new \Webiik\Translation\TranslationInjector(function () use (&$c) {
            return [$c->get('Webiik\Router\Router')];
        }));

        // Add Webiik constants
        $translation->add('WEBIIK_DEBUG', WEBIIK_DEBUG);
        $translation->add('WEBIIK_LANG', WEBIIK_LANG);
        $translation->add('WEBIIK_BASE_URI', WEBIIK_BASE_URI);
        $translation->add('WEBIIK_BASE_URL', WEBIIK_BASE_URL);
        $translation->add('WEBIIK_BASE_PATH', WEBIIK_BASE_PATH);

        return $translation;
    },

    // https://www.webiik.com/ssr/
    'Webiik\Ssr\Ssr' => function(\Webiik\Container\Container $c) {
        $ssr = new \Webiik\Ssr\Ssr();
        $ssr->useEngine(new \Webiik\Ssr\Engines\V8js());
        $ssr->setCacheDir(WEBIIK_BASE_DIR . '/tmp/components');
        return $ssr;
    },

    // https://www.webiik.com/template-helpers/
    'Webiik\Framework\TemplateHelpers' => function (\Webiik\Container\Container $c) {
        return new \Webiik\Framework\TemplateHelpers(
            $c->get('Webiik\Ssr\Ssr'),
            $c->get('Webiik\Router\Router'),
            $c->get('Webiik\Router\Route'),
            $c->get('Webiik\Translation\Translation')
        );
    },

    // https://www.webiik.com/view/
    'Webiik\View\View' => function (\Webiik\Container\Container $c) {
        $view = new \Webiik\View\View();

        // Add Twig renderer
        $view->setRenderer(function () use (&$c) {

            // Instantiate Twig template loader
            $loader = new \Twig\Loader\FilesystemLoader();

            // Add app template dir to template loader
            $loader->addPath(WEBIIK_BASE_DIR . '/frontend');

            // Instantiate Twig
            $environment = new \Twig\Environment($loader, array(
                'cache' => WEBIIK_DEBUG ? false : WEBIIK_BASE_DIR . '/tmp/view',
                'debug' => WEBIIK_DEBUG,
            ));

            // Add Twig debug extension (when errors are not silent)
            if (WEBIIK_DEBUG) {
                $environment->addExtension(new \Twig\Extension\DebugExtension());
            }

            // WebiikTwig comes with functions to work with Webiik services usually used in templates
            // eg. getURL, getRoute, _t, ...
            $environment->addExtension(new \Webiik\Framework\TwigExtension($c->get('Webiik\Framework\TemplateHelpers')));

            // Instantiate Webiik Twig renderer
            return new \Webiik\View\Renderer\Twig($environment);
        });

        return $view;
    },

    // https://www.webiik.com/database/
    'Webiik\Database\Database' => function (\Webiik\Container\Container $c) {
        $database = new \Webiik\Database\Database();

        // Get locales setting for current lang detected from URI
        $locales = $c->get('wsConfig')->get('app')['languages'][WEBIIK_LANG];

        // Get database settings
        $databaseConfArr = $c->get('wsConfig')->get('services')['Database'];

        // Add all defined databases
        foreach ($databaseConfArr as $name => $conf) {
            $conf['options'] = isset($conf['options']) ? $conf['options'] : [];
            $database->add(
                $name,
                $conf['driver'],
                $conf['host'],
                $conf['database'],
                $conf['user'],
                $conf['password'],
                $conf['options'],
                [
                    'SET CHARACTER SET ?' => str_replace('-', '', $locales[1]),
                    'SET NAMES ?' => str_replace('-', '', $locales[1]),
                    'SET time_zone = ?' => $locales[0],
                ]
            );
        }

        return $database;
    },
];