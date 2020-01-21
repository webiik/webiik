<?php
return [
//    'Webiik\Flash\Flash' => function (\Webiik\Container\Container $c) {
//        $flash = new \Webiik\Flash\Flash($c->get('Webiik\Session\Session'));
//        $flash->setLang(WEBIIK_LANG);
//        return $flash;
//    },

    'Webiik\Mail\Mail' => function (\Webiik\Container\Container $c) {
        $mail = new \Webiik\Mail\Mail();

        // Add PHPMailer
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

    'Webiik\Log\Log' => function (\Webiik\Container\Container $c) {
        $log = new \Webiik\Log\Log();

        // Configure silent mode
        $log->setSilent($c->get('wsConfig')->get('services')['Error']['silent']);

        // Add ErrorLogger for messages in group error
        $log->addLogger(function () {
            $logger = new \Webiik\Log\Logger\ErrorLogger();
            $logger->setMessageType(3);
            $logger->setDestination(WEBIIK_BASE_DIR . '/../tmp/logs/error.log');
            return $logger;
        })->setGroup('error');

        // Add MailLogger for messages in group error
        if ($c->get('wsConfig')->get('services')['Error']['silent']) {
            $log->addLogger(function () use (&$c) {
                $logger = new \Webiik\Log\Logger\MailLogger();
                $logger->setTmpDir(WEBIIK_BASE_DIR . '/../tmp/logs/sent');
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

    'Webiik\Error\Error' => function (\Webiik\Container\Container $c) {
        $error = new \Webiik\Error\Error();

        // Configure silent mode
        $error->setSilent($c->get('wsConfig')->get('services')['Error']['silent']);
        $error->setSilentPageContent('Meow, something is wrong!');

        // Write log messages using the \Webiik\Log\Log
        $error->setLogService(function ($level, $message, $data) use (&$c) {
            /** @var \Webiik\Log\Log $log */
            $log = $c->get('Webiik\Log\Log');
            $log->log($level, $message)->setData($data)->setGroup('error');
            $log->write();
        });

        return $error;
    },

    'Webiik\Arr\Arr' => function () {
        return new \Webiik\Arr\Arr();
    },

    'Webiik\Translation\Translation' => function (\Webiik\Container\Container $c) {
        $translation = new \Webiik\Translation\Translation($c['Webiik\Arr\Arr']);
        $translation->setLang(WEBIIK_LANG);

        // Add Webiik constants
        $translation->add('WEBIIK_DEBUG', WEBIIK_DEBUG);
        $translation->add('WEBIIK_LANG', WEBIIK_LANG);
        $translation->add('WEBIIK_BASE_URI', WEBIIK_BASE_URI);
        $translation->add('WEBIIK_BASE_URL', WEBIIK_BASE_URL);

        return $translation;
    },

    'Webiik\View\View' => function (\Webiik\Container\Container $c) {
        $view = new \Webiik\View\View();

        // Add Twig renderer
        $view->setRenderer(function () use (&$c) {

            // Instantiate Twig template loader
            $loader = new \Twig\Loader\FilesystemLoader();

            // Add app template dir to template loader
            // Is it an extension? Determine it by controller
            if (preg_match('~^\\\WE\\\([\w_-]+)\\\~', $c->get('Webiik\Router\Route')->getController()[0], $extName)) {
                $loader->addPath(WEBIIK_BASE_DIR . '/../extensions/' . $extName[1] . '/frontend/views');
            } else {
                $loader->addPath(WEBIIK_BASE_DIR . '/frontend/views');
            }

            // Instantiate Twig
            $environment = new \Twig\Environment($loader, array(
                'cache' => $c->get('wsConfig')->get('services')['Error']['silent'] ? WEBIIK_BASE_DIR . '/../tmp/view' : false,
                'debug' => !$c->get('wsConfig')->get('services')['Error']['silent'],
            ));

            // Add Twig debug extension (when errors are not silent)
            if (!$c->get('wsConfig')->get('services')['Error']['silent']) {
                $environment->addExtension(new \Twig\Extension\DebugExtension());
            }

            // Instantiate Webiik Twig renderer
            return new \Webiik\View\Renderer\Twig($environment);
        });

        return $view;
    },

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