<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Redirect.php';

/*
 * The sign-in page copies ?redirect= into a Location header. These tests pin
 * down that only same-site paths survive that trip, so the page cannot be used
 * to bounce someone to an attacker's site with a link that begins with the real
 * domain.
 */

test('a plain path is kept', function (): void {
    assertTrue(Redirect::isLocalPath('/admin/'), 'absolute path');
    assertTrue(Redirect::isLocalPath('../admin/listClients/'), 'relative path');
    assertTrue(Redirect::isLocalPath('/admin/listJobs/?page=2'), 'path with a query string');
});

test('absolute URLs to other sites are refused', function (): void {
    assertFalse(Redirect::isLocalPath('https://evil.example/'), 'https url');
    assertFalse(Redirect::isLocalPath('http://evil.example/'), 'http url');
    assertFalse(Redirect::isLocalPath('HTTPS://evil.example/'), 'uppercase scheme');
    assertFalse(Redirect::isLocalPath('javascript:alert(1)'), 'javascript scheme');
    assertFalse(Redirect::isLocalPath('data:text/html,<script>alert(1)</script>'), 'data scheme');
});

test('protocol-relative forms are refused', function (): void {
    // Browsers read all of these as "go to another host".
    assertFalse(Redirect::isLocalPath('//evil.example/'), 'double slash');
    assertFalse(Redirect::isLocalPath('/\\evil.example/'), 'slash backslash');
    assertFalse(Redirect::isLocalPath('\\/evil.example/'), 'backslash slash');
    assertFalse(Redirect::isLocalPath('\\\\evil.example/'), 'double backslash');
});

test('control characters are refused', function (): void {
    assertFalse(Redirect::isLocalPath("/admin/\r\nSet-Cookie: a=b"), 'crlf header injection attempt');
    assertFalse(Redirect::isLocalPath("/admin/\n"), 'trailing newline');
    assertFalse(Redirect::isLocalPath("/admin/\x00"), 'null byte');
});

test('safeTarget falls back whenever the request is unusable', function (): void {
    assertSame('../admin/', Redirect::safeTarget(null, '../admin/'), 'nothing requested');
    assertSame('../admin/', Redirect::safeTarget('', '../admin/'), 'empty request');
    assertSame('../admin/', Redirect::safeTarget(['/admin/'], '../admin/'), 'non-string request');
    assertSame('../admin/', Redirect::safeTarget('https://evil.example/', '../admin/'), 'external request');
});

test('safeTarget keeps a legitimate destination and decodes it', function (): void {
    assertSame(
        '/admin/listClients/',
        Redirect::safeTarget('%2Fadmin%2FlistClients%2F', '../admin/'),
        'the encoded path the admin pages send is decoded and kept'
    );
});

test('safeTarget refuses an encoded external URL', function (): void {
    // The admin pages urlencode() the target, so the check has to happen after
    // decoding or an encoded absolute URL would slip through.
    assertSame(
        '../admin/',
        Redirect::safeTarget('https%3A%2F%2Fevil.example%2F', '../admin/'),
        'an encoded absolute url is still refused'
    );
});
