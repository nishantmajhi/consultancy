<?php

/**
 * CSRF token issuing and checking.
 *
 * The comparison is the only subtle part: token equality must not short-circuit
 * on the first differing byte, because the time taken to answer would otherwise
 * leak how much of a guess was correct. hash_equals() is constant-time for
 * equal-length strings and is the reason this lives in its own class rather
 * than being an inline !== in each endpoint.
 */
final class Csrf
{
    /** Session key holding the current token. */
    public const SESSION_KEY = 'csrf_token';

    /** Form field and header name carrying the token back. */
    public const FIELD_NAME  = 'csrf_token';
    public const HEADER_NAME = 'HTTP_X_CSRF_TOKEN';

    /**
     * The session's token, generating one on first use.
     *
     * Requires an active session; callers reach this through Session::init().
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Whether a submitted token matches the expected one.
     *
     * Absent or non-string values are a mismatch rather than an error, so a
     * request that simply omits the token is rejected on the same path as one
     * that gets it wrong.
     */
    public static function isValid(mixed $submitted, mixed $expected): bool
    {
        if (!is_string($submitted) || !is_string($expected)) {
            return false;
        }

        if ($submitted === '' || $expected === '') {
            return false;
        }

        return hash_equals($expected, $submitted);
    }

    /**
     * The token accompanying the current request, from the POST body or from
     * the X-CSRF-Token header. The header form lets fetch() callers send a
     * token without rebuilding their request body.
     */
    public static function fromRequest(array $post, array $server): ?string
    {
        if (isset($post[self::FIELD_NAME]) && is_string($post[self::FIELD_NAME])) {
            return $post[self::FIELD_NAME];
        }

        if (isset($server[self::HEADER_NAME]) && is_string($server[self::HEADER_NAME])) {
            return $server[self::HEADER_NAME];
        }

        return null;
    }
}
