<?php
namespace Webiik;

/**
 * Class MwSecurity
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class MwSecurity
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
        header('X-Powered-By: PHP');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');

        if ($csp) {
            header('Content-Security-Policy: ' . $csp);
        }

        $next($request);
    }
}