<?php
class Session {
    private const SESSION_TIMEOUT = 1800; // 30 minutes

    public static function init() {
        date_default_timezone_set('Asia/Kathmandu');
        session_start();

        if (self::isExpired()) {
            self::terminate();
        }

        $_SESSION['last_activity'] = time();
    }

    private static function isExpired(): bool {
        return isset($_SESSION['last_activity']) &&
               (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT);
    }

    private static function terminate(): void {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    }
}