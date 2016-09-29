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
     * @var array
     */
    private static $loggers = [];

    private static $dir;

    private static $email;

    private static $subject;

    private static $tz;

    private static $maxFileSize;

    private static $maxTotalSize;

    /**
     * @param $dir
     * @param $email
     * @param bool $timezone
     * @param int $maxFileSize
     * @param int $maxTotalSize
     */
    public static function setup($dir, $email, $subject = false, $timezone = false, $maxFileSize = 2, $maxTotalSize = 20)
    {
        self::$dir = $dir;
        self::$email = $email;
        self::$subject = $subject ? $subject : 'Webiik error notice';
        self::$tz = $timezone ? $timezone : date('e');
        self::$maxFileSize = $maxFileSize;
        self::$maxTotalSize = $maxTotalSize;
    }

    /**
     * @param $name
     * @param $file
     */
    public static function addLogger($name, $file)
    {
        self::$loggers[$name] = $file;
    }

    /**
     * Log message to file and rotate logs if needed
     * @param $loggerName
     * @param $message
     * @param bool $sendMailNotice
     */
    public static function log($loggerName, array $message, $sendMailNotice = false)
    {
        // Log only when Log class is configured and logger exists
        if (self::$dir && isset(self::$loggers[$loggerName])) {

            // Prepare message
            $date = new \DateTime('now', new \DateTimeZone(self::$tz));
            $message['date'] = $date->format('Y-m-d H:i:s');
            $message['url'] = self::getRequestUrl();

            // Send email notice
            if (self::$email && $sendMailNotice) {
                if (!file_exists(self::$dir . '/!mail.' . self::$loggers[$loggerName])) {
                    mail(self::$email, self::$subject, isset($message['msg_html']) ? $message['msg_html'] : $message['msg']);
                    file_put_contents(
                        self::$dir . '/!mail.' . self::$loggers[$loggerName],
                        'Delete this file to re-activate email notifications.'
                    );
                }
            }

            // Log to file
            file_put_contents(self::$dir . '/' . self::$loggers[$loggerName], '"msg":' . json_encode($message) . ',', FILE_APPEND);
            self::rotate(self::$loggers[$loggerName]);
        }
    }

    /**
     * Rotate or delete log if log size limit is exceeded
     * @param $file
     */
    private static function rotate($file)
    {
        $file = self::$dir . '/' . $file;
        $rotated = false;

        // Rotate
        if (filesize($file) / 1048576 > self::$maxFileSize) {
            $lastDotPos = strrpos($file, '.');
            $p1 = substr($file, 0, $lastDotPos);
            $p2 = substr($file, $lastDotPos, strlen($file));
            copy($file, self::$dir . '/' . $p1 . '.' . time() . $p2);
            unlink($file);
            $rotated = true;
        }

        // Delete
        if ($rotated) {
            $logTotalSize = 0;
            $handle = opendir(self::$dir);
            while (false !== ($entry = readdir($handle))) {
                if (!is_dir($entry) && strrpos($entry, $file) !== false) {
                    if (!isset($oldestfile)) $oldestfile = $entry;
                    $logTotalSize += filesize(self::$dir . '/' . $entry) / 1048576;
                }
            }

            if ($logTotalSize > self::$maxTotalSize && isset($oldestfile)) {
                unlink(self::$dir . '/' . $oldestfile);
            }
        }
    }

    /**
     * Return host root with current scheme eg.: http://localhost
     * @return string
     */
    private static function getRequestUrl()
    {
        $pageURL = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $pageURL .= 's';
        }
        $pageURL .= '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

        return $pageURL;
    }
}