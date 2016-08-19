<?php
namespace Webiik;

class Token
{
    // Todo: Rewrite to be more universal(just generate token, compare strings) and not static
    public static $name = 'csrf_token';

    private static $strength = 16;

    /**
     * Get token value by name
     *
     * @param null $name
     * @return mixed
     * @throws \Exception
     */
    public static function getToken($name = null)
    {
        if (!isset($_SESSION)) {
            throw new \Exception('Session not found.');
        }
        if(!$name) $name = static::$name;

        return $_SESSION[$name];
    }

    /**
     * Set token in to session and return token
     *
     * @param null $strength
     * @param null $name
     * @return mixed
     * @throws \Exception
     */
    public static function setToken($strength = null, $name = null)
    {
        if (!isset($_SESSION)) {
            throw new \Exception('Session not found.');
        }
        if(!$name) $name = static::$name;
        $_SESSION[$name] = static::generateToken($strength);

        return $_SESSION[$name];
    }

    /**
     * Set token into session and return hidden input
     *
     * @param null $strength
     * @param null $name
     * @return string
     * @throws \Exception
     */
    public static function setHiddenInput($strength = null, $name = null)
    {
        if(!$name) $name = static::$name;
        return '<input type="hidden" name="'.$name.'" value="'.static::setToken($strength, $name).'"/>';
    }

    /**
     * Delete token by name
     *
     * @param null $name
     */
    public static function deleteToken($name = null)
    {
        if(!$name) $name = static::$name;
        unset($_SESSION[$name]);
    }

    /**
     * Compare token from session with token from user and return true if are equal
     *
     * @param $dataArray
     * @param null $name
     * @return bool
     * @throws \Exception
     */
    public static function validateToken($dataArray, $name = null)
    {
        if (!isset($_SESSION)) {
            throw new \Exception('Session not found.');
        }
        if (!$name) $name = static::$name;
        if (isset($_SESSION[$name]) && isset($dataArray[$name])) {
            if (!empty($_SESSION[$name]) && !empty($dataArray[$name])) {
                return static::compareStrings($_SESSION[$name], $dataArray[$name]);
            }
        }
        return false;
    }

    /**
     * Generate token value
     *
     * @param string $strength
     * @return string
     */
    public static function generateToken($strength = null)
    {
        if(!$strength) $strength = static::$strength;

        $token = '';
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
        // Todo: Maybe remove unsecure way of generating token
        if ($token == '') {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for ($i = 0; $i < $strength; $i++) {
                $token .= $characters[mt_rand(0, 61)];
            }
            $token = uniqid($token, true);
        }
        return $token;
    }

    /**
     * Timing-attack safe string comparison
     * @author Nick Volgas
     * @link https://github.com/volnix/csrf/blob/master/src/CSRF.php
     *
     * @param string $stringA
     * @param string $stringB
     * @return bool
     */
    private static function compareStrings($stringA = '', $stringB = '')
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
        if($stringsNotEqual > 0) return false;

        return true;
    }
}