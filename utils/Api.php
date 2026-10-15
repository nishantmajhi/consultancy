<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Csrf.php';

/**
 * Shared entry checks and responses for the JSON endpoints under api/.
 *
 * Every endpoint previously repeated its own auth check, method check and error
 * handling, which is why they had drifted: some returned HTTP 200 with an error
 * body, some echoed the exception message straight to the client, and only
 * three of them checked a CSRF token. Centralising the checks means an endpoint
 * gets all of them by writing one line, and a fix lands everywhere at once.
 */
final class Api
{
    /** Send the JSON content type. Safe to call more than once. */
    public static function begin(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            // Responses are per-session data; never let a shared cache hold them.
            header('Cache-Control: no-store');
        }
    }

    /**
     * Emit a JSON payload and stop.
     *
     * @param array<string, mixed> $payload
     */
    public static function send(array $payload, int $status = 200): never
    {
        self::begin();

        if (!headers_sent()) {
            http_response_code($status);
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Emit an error payload with a matching HTTP status and stop. */
    public static function fail(string $message, int $status): never
    {
        self::send(['error' => $message], $status);
    }

    /**
     * Stop unless an administrator is signed in.
     *
     * The status code matters: the previous code answered 200 with an error
     * body, so callers and monitoring could not distinguish "not signed in"
     * from a successful response.
     */
    public static function requireAuth(): void
    {
        if (empty($_SESSION['user_name'])) {
            self::fail('Unauthorized access.', 401);
        }
    }

    /** Stop unless the request method is one of those given. */
    public static function requireMethod(string ...$allowed): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if (!in_array($method, $allowed, true)) {
            if (!headers_sent()) {
                header('Allow: ' . implode(', ', $allowed));
            }

            self::fail('Method not allowed.', 405);
        }
    }

    /**
     * Stop unless the request carries the session's CSRF token.
     *
     * Applied to every state-changing endpoint. The endpoints using PATCH, PUT
     * and DELETE are already shielded from cross-origin form submission by the
     * browser's preflight, but that is a property of the browser rather than of
     * this application, so the token is checked regardless.
     */
    public static function requireCsrf(): void
    {
        $submitted = Csrf::fromRequest($_POST, $_SERVER);

        if (!Csrf::isValid($submitted, $_SESSION[Csrf::SESSION_KEY] ?? null)) {
            self::fail('CSRF token validation failed.', 403);
        }
    }

    /**
     * Report an unexpected failure without describing it to the caller.
     *
     * Exception messages here carry SQL fragments and absolute server paths, so
     * they go to the error log and the client gets a fixed sentence.
     */
    public static function serverError(Throwable $e, string $context = ''): never
    {
        error_log(sprintf(
            '[consultancy] %s%s: %s in %s:%d',
            $context,
            $context === '' ? '' : ' ',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        self::fail('Internal server error.', 500);
    }
}
