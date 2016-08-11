<?php
namespace Webiik;

/**
 * Class Error
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Error
{
    /** @var array */
    private $config = [];

    /** @var callable */
    private $silentHandler;

    /** @var callable with required params ($msgShort, $msgHtml) */
    private $logHandler;
    private $logHandlerParams = false;

    public function __construct($silent = false, $logErrors = false)
    {
        // Configure error reporting
        ini_set('log_errors', 0);
        ini_set('display_errors', 0);
        ini_set('error_reporting', E_ALL);

        $this->config['silent'] = $silent;
        $this->config['log'] = $logErrors;

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
    public function addSilentHandler(callable $handler)
    {
        $this->silentHandler = $handler;
    }

    /**
     * Add log handler
     * @param callable $handler with required params ($msgShort, $msgHtml)
     * @param $params
     */
    public function addLogHandler(callable $handler, $params)
    {
        $this->logHandler = $handler;
        $this->logHandlerParams = $params;
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
        $this->logError($type, $message, $file, $line, $trace);
        exit;
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
        } else {
            echo $this->msgHtml($type, $message, $file, $line, $trace);
        }
    }

    /**
     * If logging is on and log handler is configured
     * run log handler with params ($msgShort, $msgHtml, $params)
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     */
    private function logError($type, $message, $file, $line, $trace)
    {
        if ($this->config['log'] && is_callable($this->logHandler)) {
            $lh = $this->logHandler;
            $lh(
                $this->msgShort($type, $message, $file, $line),
                $this->msgHtml($type, $message, $file, $line, $trace),
                $this->logHandlerParams
            );
        }
    }

    /**
     * Return formatted error message as one liner
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @return string
     */
    private function msgShort($type, $message, $file, $line)
    {
        $msg = ' - ' . $type;
        $msg .= ': ' . $message;
        $msg .= ' in \'' . $file . '\'';
        $msg .= ' on line ' . $line . "\n";

        return $msg;
    }

    /**
     * Return formatted error message as html
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @param $trace
     * @return string
     */
    private function msgHtml($type, $message, $file, $line, $trace)
    {
        $msg = '<h1>' . $type . '</h1>';
        $msg .= '<b>' . $message . '</b><br/><br/>';
        $pos = strrpos($file, '/');
        $msg .= substr($file, 0, $pos + 1) . '<b>' . substr($file, $pos + 1, strlen($file)) . '</b> ';
        $msg .= '(on line: <b>' . $line . '</b>)<br/><br/>';
        if (count($trace) > 0) {
            $msg .= 'Trace:<br/>';
            foreach ($trace as $traceLine) {
                $msg .= '#' . $traceLine . '<br/>';
            }
        }
        return $msg;
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
            $err = $err[1] . ' ' . $number;
        }

        return $err;
    }
}