<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a baseline set of security-related response headers.
 *
 * The app already relies on Laravel defaults (CSRF, encrypted cookies,
 * HttpOnly sessions). These headers close a few extra clickjacking /
 * MIME-sniffing / referrer-leak vectors that Laravel does not set on
 * its own.
 *
 * Not set here:
 *  - HSTS: needs HTTPS in production; add in reverse-proxy config.
 *  - CSP:  the Tailwind CDN + Google Fonts + Alpine CDN make a strict
 *          CSP complicated. Start with a permissive policy in config
 *          once the front-end is on Vite/local bundles.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        return $response;
    }
}
