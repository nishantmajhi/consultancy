<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Csrf.php';

test('a token matches only itself', function (): void {
    $token = bin2hex(random_bytes(32));

    assertTrue(Csrf::isValid($token, $token), 'the same token validates');
    assertFalse(Csrf::isValid($token, bin2hex(random_bytes(32))), 'a different token does not');
});

test('missing or malformed tokens are a mismatch rather than an error', function (): void {
    $token = bin2hex(random_bytes(32));

    assertFalse(Csrf::isValid(null, $token), 'no token submitted');
    assertFalse(Csrf::isValid($token, null), 'no token in the session');
    assertFalse(Csrf::isValid('', ''), 'two empty strings do not match');
    assertFalse(Csrf::isValid($token, ''), 'an empty session token never matches');
    assertFalse(Csrf::isValid(['array'], $token), 'a non-string submission');
    assertFalse(Csrf::isValid(0, $token), 'an integer submission');
});

test('a token that is a prefix of the real one is rejected', function (): void {
    $token = bin2hex(random_bytes(32));

    assertFalse(Csrf::isValid(substr($token, 0, 10), $token), 'a truncated token does not validate');
    assertFalse(Csrf::isValid($token . 'x', $token), 'an extended token does not validate');
});

test('the token is read from the form field or the header', function (): void {
    assertSame(
        'from-post',
        Csrf::fromRequest(['csrf_token' => 'from-post'], []),
        'the POST field is used when present'
    );

    assertSame(
        'from-header',
        Csrf::fromRequest([], ['HTTP_X_CSRF_TOKEN' => 'from-header']),
        'the header is used when there is no field'
    );

    assertSame(
        'from-post',
        Csrf::fromRequest(['csrf_token' => 'from-post'], ['HTTP_X_CSRF_TOKEN' => 'from-header']),
        'the field wins when both are present'
    );

    assertNull(Csrf::fromRequest([], []), 'neither present');
    assertNull(Csrf::fromRequest(['csrf_token' => ['a']], []), 'a non-string field is ignored');
});

test('token() issues one token and then reuses it', function (): void {
    $_SESSION = [];

    $first  = Csrf::token();
    $second = Csrf::token();

    assertSame($first, $second, 'the same token is returned within a session');
    assertMatches('/^[0-9a-f]{64}$/', $first, 'the token is 32 random bytes in hex');

    $_SESSION = [];
});

test('token() replaces a stored value that is not a usable token', function (): void {
    $_SESSION = ['csrf_token' => ['not', 'a', 'string']];

    $token = Csrf::token();

    assertMatches('/^[0-9a-f]{64}$/', $token, 'a fresh token is generated');

    $_SESSION = [];
});
