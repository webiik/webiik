<?php

namespace Webiik;

/**
 * Class LogHandlerEmail
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class LogHandlerEmail implements LogHandlerInterface
{
    /**
     * @var \Closure
     */
    private $emailHandler;

    /**
     * Recipient email address
     * @var
     */
    private $to;

    /**
     * Sender email address
     * @var
     */
    private $from;

    /**
     * Email message subject
     * @var
     */
    private $subject;

    /**
     * Log file dir
     * @var string
     */
    private $dir;

    /**
     * Log file name
     * @var
     */
    private $fileName;

    /**
     * Log file extension
     * @var string
     */
    private $fileExtension;

    /**
     * LogHandlerEmail constructor.
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $dir
     * @param string $fileName
     * @param string $fileExtension
     * @param string $emailHandler
     */
    public function __construct($from, $to, $subject, $dir, $fileName, $fileExtension = 'log', $emailHandler = null)
    {
        $this->to = $to;
        $this->from = $from;
        $this->subject = $subject;
        $this->dir = rtrim($dir, '/');
        $this->fileName = $fileName;
        $this->fileExtension = trim($fileExtension, '.');
        $this->emailHandler = is_callable($emailHandler) ? $emailHandler : $this->sendMailHandler();
    }

    /**
     * Write message to log file and rotate/delete log file when size limit is exceeded
     * @param $data
     */
    public function write($data)
    {
        $this->send($data);
    }

    /**
     * Send email message and write file that prevents repeated sending
     * @param $data
     */
    private function send($data)
    {
        $file = $this->dir . '/!mail.' . $this->fileName . '.' . $this->fileExtension;

        if (!file_exists($file)) {
            $htmlMessage = $this->msgHtml($data);
            $send = $this->emailHandler;
            $send($this->from, $this->to, $this->subject, $htmlMessage);
            file_put_contents($file, 'Delete this file to re-activate email notifications.');
        }
    }

    /**
     * Prepare HTML message
     * @param $data
     * @return string
     */
    private function msgHtml($data)
    {
        $encMsg = json_decode($data['message'], true);

        $msg = '<b>Log level</b><br/>';
        $msg .= $data['level'];
        $msg .= '<br/><br/>';

        $msg .= '<b>Date</b><br/>';
        $msg .= $data['date'];
        $msg .= '<br/><br/>';

        $msg .= '<b>Url</b><br/>';
        $msg .= htmlspecialchars($data['url']);
        $msg .= '<br/><br/>';

        foreach ($encMsg as $key => $val) {

            $msg .= '<b>' . $key . '</b><br/>';

            if (is_array($val)) {
                foreach ($val as $row) {
                    $msg .= htmlspecialchars($row) . '<br/>';
                }
            } else {
                $msg .= htmlspecialchars($val) . '<br/>';
            }

            $msg .= '<br/>';

        }

        return $msg;
    }

    /**
     * Default send mail handler is using the PHP's mail function
     */
    private function sendMailHandler()
    {
        $sendMailHandler = function ($from, $to, $subject, $message) {

            // Encode necessary
            $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $message = base64_encode(iconv(mb_detect_encoding($message, mb_detect_order(), true), 'UTF-8', $message));

            // Email header settings
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=utf-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $headers[] = 'From: <' . $from . '>';
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            // Send email
            mail($to, $subject, $message, implode("\r\n", $headers));
        };

        return $sendMailHandler;
    }
}