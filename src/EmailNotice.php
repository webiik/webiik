<?php
namespace Webiik;

/**
 * Class EmailNoticeLogger
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class EmailNoticeLogger extends Logger
{
    /**
     * @var array
     */
    private $config = [];

    public function __construct($dir, $recipient, $timeZone = false, $subject = 'Error notice', $file = '!email_sent.txt')
    {
        $this->config['dir'] = rtrim($dir, '/');
        $this->config['recipient'] = $recipient;
        $this->config['subject'] = $subject;
        $this->config['file'] = $file;
        $this->config['timeZone'] = $timeZone ? $timeZone : date('e');
    }

    /**
     * Send email with message and create file to prevent repeated email sending
     * @param $message
     */
    public function log($message)
    {
        if (!file_exists($this->config['dir'] . '/' . $this->config['file'])) {
            $date = new \DateTime("now", new \DateTimeZone($this->config['timeZone']));
            $message = $date->format('Y-m-d H:i:s ') . $message;
            mail($this->config['recipient'], $this->config['subject'], $message);
            file_put_contents(
                $this->config['dir'] . '/' . $this->config['file'],
                'Delete this file to re-activate email notifications.'
            );
        }
    }
}