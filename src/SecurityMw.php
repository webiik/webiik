<?php
namespace Webiik;

class SecurityMw
{
    /**
     * @link https://zinoui.com/blog/security-http-headers
     * @link https://content-security-policy.com
     * @param Request $request
     * @param \Closure $next
     * @param bool $csp
     */
    public function __invoke(Request $request, \Closure $next, $csp = false)
    {
        header('X-Frame-Options: sameorigin');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');

        if ($csp) {
            header('Content-Security-Policy: ' . $csp);
        }

        $next($request);
    }
}