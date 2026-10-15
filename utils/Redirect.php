<?php

/**
 * Safe handling of the ?redirect= parameter used after signing in.
 *
 * The admin pages send an unauthenticated visitor to
 * login/?redirect=<the page they wanted>, and the login page sends them on
 * afterwards. Because that value is copied straight into a Location header, it
 * has to be confined to this application: without a check,
 * login/?redirect=https://example.com/ turns the sign-in page into an open
 * redirect, which is the standard way to make a phishing link look as though it
 * points somewhere trustworthy.
 *
 * The rule applied here is that a target must be a path within this site. Any
 * absolute URL, protocol-relative URL, or anything carrying a scheme is
 * refused, and the caller's default is used instead.
 */
final class Redirect
{
    /**
     * A destination that is guaranteed to stay on this site.
     *
     * @param mixed  $requested The raw ?redirect= value, if any.
     * @param string $fallback  Where to go when the request is absent or unsafe.
     */
    public static function safeTarget(mixed $requested, string $fallback): string
    {
        if (!is_string($requested) || $requested === '') {
            return $fallback;
        }

        $candidate = urldecode($requested);

        return self::isLocalPath($candidate) ? $candidate : $fallback;
    }

    /**
     * Whether a string is a path inside this site.
     *
     * Rejected, in order: anything with a control character or newline, which
     * would attempt header injection; a protocol-relative "//host" or the
     * backslash form browsers also accept; and anything carrying a scheme.
     */
    public static function isLocalPath(string $candidate): bool
    {
        if ($candidate === '') {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $candidate) === 1) {
            return false;
        }

        // "//evil.example" and "/\evil.example" are both read by browsers as
        // protocol-relative URLs pointing at another host.
        $firstTwo = substr($candidate, 0, 2);

        if ($firstTwo === '//' || $firstTwo === '/\\' || $firstTwo === '\\/' || $firstTwo === '\\\\') {
            return false;
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $candidate) === 1) {
            return false;
        }

        return true;
    }
}
