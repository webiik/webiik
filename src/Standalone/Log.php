<?php

namespace Webiik;

/**
 * Class Log
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Log
{
    /**
     * Custom time zone
     * @var bool|string
     */
    private $timezone;

    /**
     * Log handlers
     */
    private $handlers = [];


    /**
     * PSR-3 log levels
     * @var array
     */
    public static $DEBUG = 'DEBUG';
    public static $INFO = 'INFO';
    public static $NOTICE = 'NOTICE';
    public static $WARNING = 'WARNING';
    public static $ERROR = 'ERROR';
    public static $CRITICAL = 'CRITICAL';
    public static $ALERT = 'ALERT';
    public static $EMERGENCY = 'EMERGENCY';

    /**
     * @param $timeZone
     */
    public function setTimeZone($timeZone)
    {
        $this->timezone = $timeZone;
    }

    /**
     * System is unusable
     * @param string $loggerName
     * @param string $message
     */
    public function emergency($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$EMERGENCY);
    }

    /**
     * Action must be taken immediately
     * @param string $loggerName
     * @param string $message
     */
    public function alert($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$ALERT);
    }

    /**
     * Critical conditions
     * @param string $loggerName
     * @param string $message
     */
    public function critical($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$CRITICAL);
    }

    /**
     * Runtime errors that do not require immediate action but should typically be logged and monitored
     * @param string $loggerName
     * @param string $message
     */
    public function error($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$ERROR);
    }

    /**
     * Exceptional occurrences that are not errors
     * @param string $loggerName
     * @param string $message
     */
    public function warning($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$WARNING);
    }

    /**
     * Normal but significant events
     * @param string $loggerName
     * @param string $message
     */
    public function notice($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$NOTICE);
    }

    /**
     * Interesting events
     * @param string $loggerName
     * @param string $message
     */
    public function info($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$INFO);
    }

    /**
     * Detailed debug information
     * @param string $loggerName
     * @param string $message
     */
    public function debug($loggerName, $message)
    {
        $this->write($loggerName, $message, static::$DEBUG);
    }

    /**
     * Logs with an arbitrary level
     * @param string $loggerName
     * @param string $message
     * @param $level
     */
    public function log($loggerName, $message, $level)
    {
        $this->write($loggerName, $message, $level);
    }

    /**
     * Add log handler
     * @param $loggerName
     * @param $handlerClassName
     * @param array $handleClassNameParams
     */
    public function addHandler($loggerName, $handlerClassName, $handleClassNameParams = [])
    {
        $this->handlers[$loggerName][] = [
            'class' => $handlerClassName,
            'params' => $handleClassNameParams,
        ];
    }

    /**
     * Write message to log
     * @param $loggerName
     * @param $message
     * @param $level
     * @throws \Exception
     */
    private function write($loggerName, $message, $level)
    {
        if (empty($this->timezone)) {
            $this->timezone = date('e');
        }
        $date = new \DateTime('now', new \DateTimeZone($this->timezone));

        $message = [
            'message' => $message,
            'level' => $level,
            'date' => $date->format('Y-m-d H:i:s'),
            'ts' => time(),
            'url' => $this->getRequestUrl(),
        ];

        if (!isset($this->handlers[$loggerName])) {
            throw new \Exception('Missing logger with name \'' . htmlspecialchars($loggerName) . '\'.');
        }

        foreach ($this->handlers[$loggerName] as $handler) {
            $handlerClassName = $handler['class'];
            $handlerParams = $handler['params'];
            $this->runHandler($handlerClassName, $handlerParams, $message);
        }
    }

    /**
     * Run log handler
     * @param $handlerClassName
     * @param $handlerParams
     * @param $message
     */
    private function runHandler($handlerClassName, $handlerParams, $message)
    {
        $handler = new $handlerClassName(...$handlerParams);
        $handler->write($message);
    }

    /**
     * Return host root with current scheme eg.: http://localhost
     * @return string
     */
    private function getRequestUrl()
    {
        $pageURL = 'http';
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off' || !$_SERVER['HTTPS'])) {
            $pageURL .= 's';
        }
        $pageURL .= '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

        return $pageURL;
    }
}