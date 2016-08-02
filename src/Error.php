<?php
namespace Webiik;

/**
 * Class Route
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Error
{
    // Todo:try if the catching of PDO errors works

    /**
     * @var array
     */
    private $config = [
        'silent' => false,
        'log' => false,
        'logDir' => './',
        'logFileName' => 'errlog',
        'logMaxFileSize' => 2, // in MB
        'logMaxTotalSize' => 20, // in MB
        'mail' => false,
        'mailTo' => false,
        'subject' => 'Error report',
    ];

    /**
     * @var callable
     */
    private $silentHandler;

    public function __construct($config = [])
    {
        // Configure class
        $this->config = array_merge($this->config, $config);
        $this->config['logDir'] = rtrim($this->config['logDir'], '/');

        // Configure error reporting
        ini_set('log_errors', 0);
        ini_set('display_errors', 0);
        ini_set('error_reporting', E_ALL);

        // Pre-define some silent handler
        $this->silentHandler = function () {
            echo '<h1>Unexpected situation:(</h1>';
            echo '<p>We are working on it.</p>';
        };

        // Set custom error handlers
        set_error_handler($this->errorHandler());
        register_shutdown_function($this->shutdownHandler());
        set_exception_handler($this->exceptionHandler());
    }

    /**
     * Register user defined silent message handler.
     * Use this to define your own silent message.
     * @param callable $handler
     */
    public function silentHandler(callable $handler)
    {
        $this->silentHandler = $handler;
    }

    /**
     * Translate PHP error to human readable string
     * @param $number
     * @return array
     */
    private function parseErrorType($number)
    {
        $err = [
            1 => 'Error',
            2 => 'Warning',
            4 => 'ParseError',
            8 => 'Notice',
            64 => 'FatalError',
        ];

        if (isset($err[$number])) {
            $err = $err[$number];
        } else {
            $err = $number;
        }

        return $err;
    }

    /**
     * Return closure to handle PHP exceptions
     * @return \Closure
     */
    private function exceptionHandler()
    {
        $exceptionHandler = function (\Exception $e) {
            $trace = explode('#', $e->getTraceAsString());
            unset($trace[0]);
            $this->outputError('Exception', $e->getMessage(), $e->getFile(), $e->getLine(), $trace);
        };
        return $exceptionHandler;
    }

    /**
     * Return closure to handle PHP errors
     * @return \Closure
     */
    private function errorHandler()
    {
        $errorHandler = function ($errno, $errstr, $errfile, $errline) {
            $this->outputError($this->parseErrorType($errno), $errstr, $errfile, $errline);
        };
        return $errorHandler;
    }

    /**
     * Return closure to handle PHP shutdown
     * @return \Closure
     */
    private function shutdownHandler()
    {
        $shutdownHandler = function () {
            $err = error_get_last();
            if ($err) {
                $this->outputError($this->parseErrorType($err['type']), $err['message'], $err['file'], $err['line']);
            }
        };
        return $shutdownHandler;
    }

    /**
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @param array $trace
     */
    private function outputError($type, $message, $file, $line, $trace = [])
    {
        $this->printError($type, $message, $file, $line, $trace);
        $this->logError($type, $message, $file, $line);
        $this->mailError($type, $message, $file, $line, $trace);
    }

    /**
     * Print error to screen, but in silent mode don't print error and just show silent message.
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @param $trace
     */
    private function printError($type, $message, $file, $line, $trace)
    {
        if ($this->config['silent']) {
            $sh = $this->silentHandler;
            $sh();
            exit;
        } else {
            echo $this->message($type, $message, $file, $line, $trace);
        }
    }

    /**
     * Log error to file and rotate logs if needed
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     */
    private function logError($type, $message, $file, $line)
    {
        if ($this->config['log']) {

            $msg = DATE('Y-m-d H:i:s');
            $msg .= ' - ' . $type;
            $msg .= ': ' . $message;
            $msg .= ' in \'' . $file . '\'';
            $msg .= ' on line ' . $line . "\n";
            file_put_contents($this->config['logDir'] . '/' . $this->config['logFileName'] . '.log', $msg, FILE_APPEND);

            $this->rotate();
        }
    }

    /**
     * Rotate or delete log if log size limit is exceeded
     */
    private function rotate()
    {
        $file = $this->config['logDir'] . '/' . $this->config['logFileName'] . '.log';
        $rotated = false;

        // Rotate
        if (filesize($file) / 1048576 > $this->config['logMaxFileSize']) {
            copy($file, $this->config['logDir'] . '/' . $this->config['logFileName'] . '.' . time() . '.log');
            unlink($file);
            $rotated = true;
        }

        // Delete
        if ($rotated) {
            $logTotalSize = 0;
            $handle = opendir($this->config['logDir']);
            while (false !== ($entry = readdir($handle))) {
                if (!is_dir($entry) && strrpos($entry, $this->config['logFileName']) !== false) {
                    if (!isset($oldestLogFile)) $oldestLogFile = $entry;
                    $logTotalSize += filesize($this->config['logDir'] . '/' . $entry) / 1048576;
                }
            }

            if ($logTotalSize > $this->config['logMaxTotalSize'] && isset($oldestLogFile)) {
                unlink($this->config['logDir'] . '/' . $oldestLogFile);
            }
        }
    }

    /**
     * Send email notification if error occurs then stop sending emails
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @param $trace
     */
    private function mailError($type, $message, $file, $line, $trace)
    {
        if ($this->config['mail'] && $this->config['mailTo']
            && !file_exists($this->config['logDir'] . '/!mail_sent.log')
        ) {
            mail(
                $this->config['mailTo'],
                $this->config['subject'],
                $this->message($type, $message, $file, $line, $trace)
            );

            file_put_contents(
                $this->config['logDir'] . '/!mail_sent.log',
                'Delete this file to again active email notifications.'
            );
        }
    }

    /**
     * Return formatted error message
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @param $trace
     * @return string
     */
    private function message($type, $message, $file, $line, $trace)
    {
        $msg = '<h1>' . $type . '</h1>';
        $msg .= '<b>' . $message . '</b><br/><br/>';
        $pos = strrpos($file, '/');
        $msg .= substr($file, 0, $pos + 1) . '<b>' . substr($file, $pos + 1, strlen($file)) . '</b> ';
        $msg .= '(on line: <b>' . $line . '</b>)<br/><br/>';
        if (count($trace)>0) {
            $msg .= 'Trace:<br/>';
            foreach ($trace as $traceLine) {
                $msg .= '#' . $traceLine . '<br/>';
            }
        }
        return $msg;
    }
}