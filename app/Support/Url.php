<?php

namespace App\Support;

class Url
{
    /**
     * Schemes a QR code or short link may ever send a scanner to.
     */
    public const ALLOWED_SCHEMES = ['http', 'https'];

    public static function isSafe(mixed $url): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $url = trim($url);

        // Control characters are stripped by browsers before the scheme is read,
        // so "java\nscript:" would slip past a naive scheme check.
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($scheme) || ! in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            return false;
        }

        if (! is_string($host) || $host === '') {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * The URL when it is safe to put in an href or a Location header, else null.
     */
    public static function safeOrNull(mixed $url): ?string
    {
        return self::isSafe($url) ? trim((string) $url) : null;
    }
}
