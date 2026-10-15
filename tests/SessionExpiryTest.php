<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';

/*
 * Session::isExpiredAt() takes its clock and timestamps as arguments precisely
 * so the rules can be checked here without starting a session or waiting.
 */

test('a fresh session has not expired', function (): void {
    $now = 1_757_000_000;

    assertFalse(
        Session::isExpiredAt($now, $now, $now, 1800, 43200),
        'a session used just now is live'
    );
});

test('a session expires once it has been idle past the timeout', function (): void {
    $now = 1_757_000_000;

    assertFalse(Session::isExpiredAt($now - 1799, $now - 1799, $now, 1800, 43200), 'just inside the idle window');
    assertFalse(Session::isExpiredAt($now - 1800, $now - 1800, $now, 1800, 43200), 'exactly at the idle limit');
    assertTrue(Session::isExpiredAt($now - 1801, $now - 1801, $now, 1800, 43200), 'one second past the idle limit');
});

test('a session expires at the absolute lifetime even when constantly used', function (): void {
    $now = 1_757_000_000;

    // Active one second ago, but signed in longer ago than the absolute limit.
    // Without this rule a tab polling in the background keeps a session alive
    // indefinitely.
    assertTrue(
        Session::isExpiredAt($now - 1, $now - 43201, $now, 1800, 43200),
        'an old session ends despite recent activity'
    );

    assertFalse(
        Session::isExpiredAt($now - 1, $now - 43199, $now, 1800, 43200),
        'just inside the absolute lifetime'
    );
});

test('a session with no recorded timestamps is not treated as expired', function (): void {
    $now = 1_757_000_000;

    // This is the first request of a brand new session.
    assertFalse(Session::isExpiredAt(null, null, $now, 1800, 43200), 'nothing recorded yet');
});

test('either rule alone is enough to expire a session', function (): void {
    $now = 1_757_000_000;

    assertTrue(Session::isExpiredAt($now - 9999, null, $now, 1800, 43200), 'idle rule with no start time');
    assertTrue(Session::isExpiredAt(null, $now - 99999, $now, 1800, 43200), 'absolute rule with no activity time');
});
