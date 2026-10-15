<?php

/**
 * Session lifecycle for the admin area.
 *
 * Three things are enforced here that the browser will not do on its own:
 *
 *   1. Cookie flags. HttpOnly keeps the session cookie out of reach of any
 *      script on the page, so a cross-site scripting bug elsewhere in the
 *      application cannot turn into a stolen session. SameSite=Lax stops the
 *      cookie riding along on cross-site form posts. Secure is set whenever the
 *      request arrived over HTTPS.
 *   2. Two expiries. An idle timeout ends a session left unattended; an
 *      absolute lifetime ends one that is kept alive indefinitely by a tab
 *      polling in the background.
 *   3. An expiry response that suits the caller. A page gets a redirect to the
 *      login screen; a JSON endpoint gets 401 with a JSON body, because
 *      answering an API call with a redirect to HTML gives the caller something
 *      it cannot parse.
 */
class Session
{
    /** Seconds of inactivity after which the session ends. */
    private const IDLE_TIMEOUT = 1800;

    /** Seconds after sign-in beyond which the session ends regardless of use. */
    private const ABSOLUTE_LIFETIME = 43200;

    /** Start or resume the session, enforcing both expiry rules. */
    public static function init(): void
    {
        date_default_timezone_set('Asia/Kathmandu');

        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => self::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        if (self::hasExpired()) {
            self::terminate();
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Record a successful sign-in.
     *
     * The identifier is regenerated so that a session id an attacker managed to
     * plant before sign-in is not the one that ends up authenticated.
     */
    public static function start(string $userName): void
    {
        self::init();

        session_regenerate_id(true);

        $_SESSION['user_name']     = $userName;
        $_SESSION['started_at']    = time();
        $_SESSION['last_activity'] = time();
    }

    /** Whether an administrator is currently signed in. */
    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_name']);
    }

    /** End the session and clear its cookie. */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Whether either expiry rule has been passed.
     *
     * Public and parameterised so the rules can be tested without a live
     * session; see tests/SessionExpiryTest.php.
     */
    public static function isExpiredAt(
        ?int $lastActivity,
        ?int $startedAt,
        int $now,
        int $idleTimeout = self::IDLE_TIMEOUT,
        int $absoluteLifetime = self::ABSOLUTE_LIFETIME
    ): bool {
        if ($lastActivity !== null && ($now - $lastActivity) > $idleTimeout) {
            return true;
        }

        return $startedAt !== null && ($now - $startedAt) > $absoluteLifetime;
    }

    private static function hasExpired(): bool
    {
        return self::isExpiredAt(
            isset($_SESSION['last_activity']) ? (int) $_SESSION['last_activity'] : null,
            isset($_SESSION['started_at']) ? (int) $_SESSION['started_at'] : null,
            time()
        );
    }

    /**
     * End an expired session and tell the caller in the form it expects.
     *
     * The previous implementation redirected to a relative "index.php", which
     * from an endpoint under api/ resolved to a path inside that directory
     * rather than to the login page.
     */
    private static function terminate(): never
    {
        self::destroy();

        if (self::wantsJson()) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(401);
            }

            echo json_encode(['error' => 'Session expired.']);
            exit;
        }

        if (!headers_sent()) {
            header('Location: /login/');
        }

        exit;
    }

    /** True when the request is an API call rather than a page navigation. */
    private static function wantsJson(): bool
    {
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        if (str_contains($path, '/api/')) {
            return true;
        }

        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return str_contains($accept, 'application/json');
    }

    /** True when the current request arrived over HTTPS, including behind a proxy. */
    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
