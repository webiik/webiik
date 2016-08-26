<?php
namespace Webiik;

class Validate
{
    public function email($email)
    {
        $email = mb_strtolower(strtolower(str_replace('..', '.', trim($email, " .\t\n\r\0\x0B"))));

        if (mb_strlen($email) > 60) {
            return false;
        }

        if (preg_match('/^[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*@[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~]*\.[^\\\@\!\#\$\%\&\"\'\*\+\/\=\?\^\_\`\{\|\}\(\)\,\:\;\<\>\@\[\]\s\~\.]{2,63}$/', $email)) {
            return false;
        }

        return $email;
    }

    private function validatePswd($pswd)
    {
        return $pswd;
    }
}