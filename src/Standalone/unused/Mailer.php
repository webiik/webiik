<?php

namespace Webiik;

/**
 * Class Mailer
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
// Todo: Finish Mailer class
class Mailer
{
    private $isSmtp = false;

    private $smtpServer;

    private $smtpPort;

    private $smtpSecure = 'tls';

    private $isAuth = false;

    private $smtpUserName;

    private $smtpPassword;

    private $smtpOptions = [];

    private $isHtml = false;

    private $attachements = [];

    private $senderEmail;

    private $senderName;

    private $replyToEmails = [];

    public function isSmtp($bool)
    {
        $this->isSmtp = $bool;
    }

    public function setSmtpServer($address)
    {
        $this->smtpServer = $address;
    }

    public function setSmtpPort($portNumber)
    {
        $this->smtpPort = $portNumber;
    }

    public function setSmtpSecure($protocol)
    {
        $this->smtpSecure = $protocol;
    }

    public function setIsAuth($bool)
    {
        $this->isAuth = $bool;
    }

    public function setSmtpUserName($name)
    {
        $this->smtpUserName = $name;
    }

    public function setSmtpPassword($password)
    {
        $this->smtpPassword = $password;
    }

    public function setSmtpOptions($options = [])
    {
        $this->smtpOptions = $options;
    }

    public function setIsHtml($bool)
    {
        $this->isHtml = $bool;
    }

    public function addAttachement($filePath)
    {
    }

    public function addStringAttachment($string, $fileName)
    {
    }

    public function setSenderEmail($email)
    {
        $this->senderEmail = $email;
    }

    public function setSenderName($name)
    {
        $this->senderName = $name;
    }

    public function addReplyToEmail($email)
    {
        $this->replyToEmails[] = $email;
    }

    public function send($recipient, $subject, $message)
    {
        // Encode necessary
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromName = '=?UTF-8?B?' . base64_encode($this->senderName) . '?=';
        $message = base64_encode(iconv(mb_detect_encoding($message, mb_detect_order(), true), 'UTF-8', $message));

        // Email header settings
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=utf-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $headers[] = 'From: ' . $this->senderName . ' <' . $this->senderEmail . '>';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        // Send email
        mail($recipient, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * Prevent attacks similar to CVE-2016-10033 and CVE-2016-10045 by disallowing
     * potentially unsafe shell characters.
     *
     * Note that escapeshellarg and escapeshellcmd are inadequate for this purpose,
     * especially on Windows.  Additionally, sanitization won't be sufficient for
     * strings that must pass through escapeshellcmd, which is used by many built-in
     * PHP functions, including mail().  This function will return true for strings
     * that will be safe in all but the most obscure environments.  It does assume
     * that locale has been configured correctly, both in the shell and PHP, or that
     * an ASCII-compatible charset is in use, at the very least.
     *
     * This function may allow non-Latin characters in some locales.  This should
     * not present a problem on a properly configured system, or even most
     * improperly configured systems.  If you need to enforce 7-bit ASCII, set the
     * current language to "C".
     *
     * Designed for POSIX (Linux, Mac, BSD) and Windows.
     *
     * @param   string $string the string to be tested for shell safety
     * @param   boolean $emptyIsSafe whether empty strings are to be considered
     *                               safe; do not enable unless you know what you're
     *                               doing, as this can be exploited
     * @return  boolean true if the string is guaranteed safe; otherwise, false
     * @throws  \LogicException if the current locale behaves in such a way that
     *                          security cannot be guaranteed, if the programmer
     *                          does something unsafe, or if PHP lacks the necessary
     *                          functions to perform adequate checks
     * @uses    \LogicException
     *
     * @see     https://gist.github.com/Zenexer/40d02da5e07f151adeaeeaa11af9ab36
     *          Detailed discussion of the underlying issue and up-to-date code.
     * @author  Paul Buonopane <paul@namepros.com>
     *          CTO of NamePros
     *          https://github.com/Zenexer
     * @license Public doman per CC0 1.0.  Attribution appreciated but not required.
     *          https://creativecommons.org/publicdomain/zero/1.0/
     */
    private function isShellSafe($string, $emptyIsSafe = false)
    {
        static $safeSymbols = array('@' => true, '_' => true, '-' => true, '.' => true);
        static $safeRanges = null;
        if (!isset($safeRanges)) {
            $safeRanges = array(
                array(ord('0'), ord('9')),
                array(ord('A'), ord('Z')),
                array(ord('a'), ord('z')),
            );
        }

        static $asciiCompatCharsets = array(
            'C', 'POSIX', 'ASCII',
            'UTF-8', 'utf8',
            'ISO-646',
            'ISO-8859',
            // Note that EUC/ISO-2022 is NOT compatible.

            // Western
            'ISO-8859-1', 'Latin-1', 'Windows-1252',       // Western European
            'ISO-8859-2', 'Latin-2', 'Windows-28592',      // Eastern European
            'ISO-8859-3', 'Latin-3', 'Windows-28593',      // South European
            'ISO-8859-4', 'Latin-4', 'Windows-28594',      // North European
            'ISO-8859-7', 'Windows-28597',      // Greek
            'ISO-8859-10', 'Latin-6',                        // Nordic
            'ISO-8859-13', 'Latin-7', 'Windows-28603',      // Baltic Rim
            'ISO-8859-14', 'Latin-8',                        // Celtic
            'ISO-8859-15', 'Latin-9', 'Windows-28605',      // Western European
            'ISO-8859-16', 'Latin-10',                       // South-Eastern European


            // Cyrillic
            'ISO-8859-5', 'Latin-5', 'Windows-28595',
            'Windows-1251',
            'KOI8-R', 'Windows-20866',
            'KOI8-U', 'Windows-21866',
            'KOI-7', 'KOI7',

            // Middle Eastern
            'ISO-8859-6', 'Windows-28596', 'Windows-38596',  // Arabic
            'ISO-8859-8', 'Windows-28598', 'Windows-38598',  // Hebrew
            'ISO-8859-9', 'Windows-28599',                   // Turkish

            // East/South Asian
            'ISO-8859-11', 'TIS-620', 'Windows-874',         // Thai
            'Shift-JIS', 'SHIFT_JIS', 'SJIS',                // Japanese
            'Windows-936', 'CP1386',                         // Chinese
            'GB2312',                                        // Chinese
        );

        // Remove this if you've read the param description and are absolutely certain you know what you're doing.
        if ($emptyIsSafe) {
            throw new \LogicException('Are you really sure you want to do that?  Please read the param description before enabling $emptyIsSafe.');
        }
        // End of disclaimer

        $string = strval($string);
        $length = strlen($string);

        if (!$length) {
            return $emptyIsSafe === true;
        }

        // Future-proof
        if (escapeshellcmd($string) !== $string || !in_array(escapeshellarg($string), array("'$string'", "\"$string\""))) {
            return false;
        }

        // Decide on a method.  You can reprioritize these.
        static $method = null;
        if (!isset($method)) {
            if (function_exists('preg_match')) {
                // Most versatile
                $method = 'pcre';
            } elseif (function_exists('ctype_alnum')) {
                // May not handle stateful encodings like EUC correctly
                $method = 'ctype';
            } else {
                // Fallback; require ASCII, ISO-8859, or UTF-8
                $method = 'invalid';  // We'll set it to 'binary' if our tests pass

                $locale = null;
                if (function_exists('setlocale')) {
                    $locale = setlocale('LC_CTYPE', 0);
                    if (!$locale) {
                        $locale = setlocale('LC_ALL', 0);
                    }
                } else {
                    if (!empty($_ENV['LC_CTYPE'])) {
                        $locale = $_ENV['LC_CTYPE'];
                    } elseif (!empty($_ENV['LC_ALL'])) {
                        $locale = $_ENV['LC_ALL'];
                    } elseif (!empty($_ENV['LANG'])) {
                        $locale = $_ENV['LANG'];
                    }
                }

                if (!$locale) {
                    throw new LogicException('Unable to determine current locale.  Ideally, PHP should be compiled with ctype or PCRE to avoid this issue.');
                }

                // $locale could be a string with multiple vars separated by
                // semicolons.  We're primarily interested in the earlier vars.
                $tok = explode(';', $locale, 2);
                $tok = explode('=', $tok[0], 2);
                $locale = end($tok);

                // Try to extract the charset
                $tok = explode('.', $locale, 2);
                $charset = end($tok);

                if (!in_array($charset, $asciiCompatCharsets)) {
                    throw new \LogicException("Can't be certain that the current charset is ASCII-compatible.  Ideally, PHP should be compiled with ctype or PCRE to avoid this issue.");
                }

                $method = 'binary';
            }
        }

        switch ($method) {
            case 'pcre':
                return (bool)preg_match('/\A[\pL\pN._@-]+\z/ui', $string);

            case 'ctype':
                for ($i = 0; $i < $length; $i++) {
                    if (!ctype_alnum($string[$i]) && !isset($safeSymbols[$string[$i]])) {
                        return false;
                    }
                }
                return true;

            case 'binary':
                for ($i = 0; $i < $length; $i++) {
                    if (isset($safeSymbols[$string[$i]])) {
                        continue;  // Char is valid; next
                    }

                    $c = ord($string[$i]);
                    foreach ($safeRanges as $range) {
                        if ($c >= $range[0] && $c <= $range[1]) {
                            continue 2;  // Char is value; next
                        }
                    }
                    return false;  // Char is invalid
                }
                return true;  // End of string, all chars were valid

            case 'invalid':
                throw new \LogicException('Initialization previously failed');

            default:
                throw new \LogicException('Invalid method');
        }

        throw new \LogicException('Unknown control flow error');
    }

    private function isEmailAddressValid($email)
    {
        $pattern = '/^[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\@[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\.[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~\.]{2,63}$/';
        return preg_match($pattern, $email);
    }

    private function sendCommand()
    {

    }
}