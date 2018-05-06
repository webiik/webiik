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
    /**
     * Indicates if error will be displayed silently
     * @var bool
     */
    private $silent = false;

    /**
     * @var
     */
    private $logger;

    /** @var callable */
    private $silentHandler;

    /**
     * Error constructor.
     * @param bool $silent
     */
    public function __construct($silent = false)
    {
        // Configure error reporting
        ini_set('log_errors', 0);
        ini_set('display_errors', 0);
        ini_set('error_reporting', E_ALL);

        $this->silent = $silent;

        // Pre-define some silent handler
        $this->silentHandler = function () {
            echo '<h1>Unexpected situation</h1>';
            echo '<p>We are working on it.</p>';
        };

        // Set custom error handlers
        set_error_handler($this->errorHandler());
        register_shutdown_function($this->shutdownHandler());
        set_exception_handler($this->exceptionHandler());
    }

    /**
     * Set true if errors should not be displayed
     * @param bool $bool
     */
    public function setSilent($bool)
    {
        $this->silent = $bool;
    }

    /**
     * Add logger to enable error logging
     * @param callable $logger
     */
    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Register user defined silent message handler.
     * Use this to define your own silent message.
     * @param \Closure $handler
     */
    public function setSilentHandler(\Closure $handler)
    {
        $this->silentHandler = $handler;
    }

    /**
     * Return closure to handle PHP exceptions
     * @return \Closure
     */
    private function exceptionHandler()
    {
        /** @param \Error|\Exception $e */
        $exceptionHandler = function ($e) {
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
            if ($errno && error_reporting()) {
                $this->outputError($this->parseErrorType($errno), $errstr, $errfile, $errline);
            }
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
        // If trace is empty, generate trace from debug_backtrace()
        if (empty($trace)) {
            $dt = debug_backtrace();
            $i = count($dt);
            foreach ($dt as $caller) {
                $msg = 'called by \'' . $caller['function'] . '\'';
                if (isset($caller['class'])) {
                    $msg .= ', class \'' . $caller['class'] . '\'';
                }
                if (isset($caller['file'])) {
                    $msg .= ' in file \'' . $caller['file'] . ' (on line: ' . $caller['line'] . ')\'';
                }
                $trace[] = $i . ' ' . $msg;
                $i--;
            }
        }

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
        if ($this->silent) {
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
     * @param $trace
     */
    private function logError($type, $message, $file, $line, $trace)
    {
        $msg = [
            'Error type' => $type,
            'File' => $file,
            'Line' => $line,
            'Message' => $message,
            'Trace' => $trace,
        ];
        $this->log($msg);
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
            4096 => 'CatchableFatalError',
        ];

        if (isset($err[$number])) {
            $err = $err[$number];
        } else {
            $err = $err[1] . ' ' . $number;
        }

        return $err;
    }

    /**
     * Log error
     * @param $data
     */
    private function log($data)
    {
        $logger = $this->logger;

        if (is_callable($logger)) {
            $logger($data);
        } else {
            error_log(json_encode($data) . '\r\n');
        }
    }
}