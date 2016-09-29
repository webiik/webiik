<?php
namespace Webiik;

class Token
{
    /**
     * Generate token
     * @param int $strength
     * @return bool|string
     */
    public function generate($strength = 16)
    {
        $token = false;

        if (function_exists('random_bytes')) {
            $rawToken = random_bytes($strength);
            if ($rawToken !== false) {
                $token = bin2hex($rawToken);
            }
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $rawToken = openssl_random_pseudo_bytes($strength);
            if ($rawToken !== false) {
                $token = bin2hex($rawToken);
            }
        }

        return $token;
    }

    /**
     * Generate fast but unsafe token
     * @param int $length
     * @return string
     */
    public function generateCheap($length = 16)
    {
        $token = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[mt_rand(0, 61)];
        }
        return $token;
    }

    /**
     * Timing-attack safe string comparison
     * @author Nick Volgas
     * @link https://github.com/volnix/csrf/blob/master/src/CSRF.php
     * @param string $stringA
     * @param string $stringB
     * @return bool
     */
    public function compare($stringA = '', $stringB = '')
    {
        $stringsNotEqual = strlen($stringA) ^ strlen($stringB);

        // find shortest string, this just keeps us from over-flowing string when comparing
        $length = min(strlen($stringA), strlen($stringB));
        $stringA = substr($stringA, 0, $length);
        $stringB = substr($stringB, 0, $length);

        // iterate through the string comparing them character by character
        for ($i = 0; $i < $length; $i++) {
            // if a character does not match return true
            $stringsNotEqual = $stringsNotEqual + !(ord($stringA[$i]) === ord($stringB[$i]));
        }

        // if we have some true then hashes are not equal
        if ($stringsNotEqual > 0) return false;

        return true;
    }
}