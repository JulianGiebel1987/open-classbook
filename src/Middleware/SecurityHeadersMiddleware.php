<?php

namespace OpenClassbook\Middleware;

use OpenClassbook\App;

class SecurityHeadersMiddleware
{
    public function handle(): bool
    {
        // MIME-Type-Sniffing verhindern
        header('X-Content-Type-Options: nosniff');

        // Clickjacking-Schutz
        header('X-Frame-Options: SAMEORIGIN');

        // XSS-Filter für ältere Browser
        header('X-XSS-Protection: 1; mode=block');

        // Referrer-Informationen einschraenken
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Browser-Features einschraenken
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // HSTS nur bei aktiver HTTPS-Verbindung
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        if (App::config('security.csp_enabled') ?? true) {
            $csp = "default-src 'self'; "
                 . "script-src 'self'; "
                 . "style-src 'self' 'unsafe-inline'; "
                 . "img-src 'self' data:; "
                 . "font-src 'self'; "
                 . "form-action 'self'; "
                 . "frame-ancestors 'self'; "
                 . "base-uri 'self'; "
                 . "object-src 'none'";

            $headerName = (App::config('security.csp_report_only') ?? false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            header($headerName . ': ' . $csp);
        }

        return true;
    }
}
