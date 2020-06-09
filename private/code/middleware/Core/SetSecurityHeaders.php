<?php
declare(strict_types=1);

namespace Webiik\Middleware\Core;

class SetSecurityHeaders
{
    public function run(\Webiik\Data\Data $data, callable $next): void
    {
        header('X-Frame-Options: sameorigin');
        header('X-Powered-By: PHP');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        $next();
    }
}