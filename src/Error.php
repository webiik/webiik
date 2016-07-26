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
    // Todo:now Config
    // Todo:now Logging
    // Todo:now Email notices
    // Todo:maybe Debug bar

    /**
     * Exception constructor.
     */
    public function __construct()
    {
        ini_set('log_errors', 0);
        ini_set('display_errors', 0);
        ini_set('error_reporting', E_ALL);
        set_error_handler($this->errorHandler());
        register_shutdown_function($this->shutdownHandler());
        set_exception_handler($this->exceptionHandler());
    }

    private function parseErrorType($number)
    {
        $err = [
            1 => 'Error',
            2 => 'Warning',
            4 => 'ParseError',
            8 => 'Notice',
        ];

        if (isset($err[$number])) {
            $err = $err[$number];
        } else {
            $err = $number;
        }

        return $err;
    }

    private function exceptionHandler()
    {
        $exceptionHandler = function (\Exception $e) {
            $trace = explode('#', $e->getTraceAsString());
            unset($trace[0]);

            $this->printError('Exception', $e->getMessage(), $e->getFile(), $e->getLine(), $trace);
        };
        return $exceptionHandler;
    }

    private function errorHandler()
    {
        $errorHandler = function ($errno, $errstr, $errfile, $errline) {
            $this->printError($this->parseErrorType($errno), $errstr, $errfile, $errline, []);
        };
        return $errorHandler;
    }

    private function shutdownHandler()
    {
        $shutdownHandler = function () {
            $err = error_get_last();
            if ($err) {
                $this->printError($this->parseErrorType($err['type']), $err['message'], $err['file'], $err['line'], []);
            }
        };
        return $shutdownHandler;
    }

    private function printError($type, $message, $file, $line, $trace)
    {
        echo '<h1>' . $type . '</h1>';
        echo '<b>' . $message . '</b><br/><br/>';
        $pos = strrpos($file, '/');
        echo 'In: <br/>' . substr($file, 0, $pos + 1) . '<b>' . substr($file, $pos + 1, strlen($file)) . '</b><br/><br/>';
        echo 'On line: <br/><b>' . $line . '</b><br/><br/>';
        if (isset($trace[0])) {
            echo 'Trace:<br/>';
            foreach ($trace as $traceLine) {
                echo '#' . $traceLine . '<br/>';
            }
        }
    }
}