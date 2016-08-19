<?php
/**
 * Created by PhpStorm.
 * User: mihi
 * Date: 27/06/16
 * Time: 11:54
 */

namespace Webiik;


class Mail
{
    public function send($fromMail, $fromName, $toMail, $subject, $message)
    {
        // Encode necessary
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $message = base64_encode(iconv(mb_detect_encoding($message, mb_detect_order(), true), 'UTF-8', $message));

        // Email header settings
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=utf-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $headers[] = 'From: ' . $fromName . ' <' . $fromMail . '>';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        // Send email
        mail($toMail, $subject, $message, implode("\r\n", $headers));
    }

}