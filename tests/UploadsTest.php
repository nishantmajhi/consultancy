<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Uploads.php';

/*
 * The upload path is the one that previously allowed any file to be written
 * into a directory the web server executes PHP from. These tests exist to make
 * that specific hole impossible to reopen quietly, so they lean heavily on the
 * rejection cases rather than the happy path.
 */

test('extensionOf reads the extension in lower case', function (): void {
    assertSame('pdf', Uploads::extensionOf('report.PDF'), 'uppercase extension is normalised');
    assertSame('jpeg', Uploads::extensionOf('holiday.jpeg'), 'plain extension');
    assertSame('png', Uploads::extensionOf('archive.tar.png'), 'only the final extension counts');
});

test('extensionOf ignores any directory component in the client filename', function (): void {
    assertSame('jpg', Uploads::extensionOf('../../evil/x.jpg'), 'unix separators are stripped');
    assertSame('jpg', Uploads::extensionOf('..\\..\\evil\\x.jpg'), 'windows separators are stripped');
    assertSame('', Uploads::extensionOf('../../../etc/passwd'), 'a path with no extension yields none');
});

test('extensionOf returns nothing when there is no usable extension', function (): void {
    assertSame('', Uploads::extensionOf('noextension'), 'no dot at all');
    assertSame('', Uploads::extensionOf('trailingdot.'), 'a trailing dot is not an extension');
    assertSame('', Uploads::extensionOf(''), 'empty filename');
});

test('only allowlisted extensions are accepted', function (): void {
    foreach (['pdf', 'jpg', 'jpeg', 'png', 'webp'] as $allowed) {
        assertTrue(Uploads::isAllowedExtension($allowed), $allowed . ' is on the allowlist');
    }

    foreach (['php', 'phtml', 'php5', 'phar', 'html', 'svg', 'exe', 'sh', 'js'] as $blocked) {
        assertFalse(Uploads::isAllowedExtension($blocked), $blocked . ' is rejected');
    }
});

test('image-only fields reject documents', function (): void {
    assertTrue(Uploads::isAllowedExtension('png', true), 'png is an image');
    assertFalse(Uploads::isAllowedExtension('pdf', true), 'pdf is not accepted where an image is required');
});

test('the sniffed type must agree with the extension', function (): void {
    assertTrue(Uploads::mimeMatchesExtension('png', 'image/png'), 'matching pair is accepted');
    assertTrue(Uploads::mimeMatchesExtension('jpg', 'image/jpeg'), 'jpg maps to image/jpeg');

    // A PHP script renamed to .jpg reports text/x-php or text/plain, never image/jpeg.
    assertFalse(Uploads::mimeMatchesExtension('jpg', 'text/x-php'), 'a php script named .jpg is rejected');
    assertFalse(Uploads::mimeMatchesExtension('jpg', 'text/plain'), 'a text file named .jpg is rejected');

    // A genuine image whose name ends in .php is rejected by the extension rule,
    // and would also fail here because php is not on the allowlist at all.
    assertFalse(Uploads::mimeMatchesExtension('php', 'image/gif'), 'a gif named .php is rejected');
});

test('check rejects a php script disguised with an image extension', function (): void {
    $verdict = Uploads::check('shell.jpg', 120, UPLOAD_ERR_OK, 'text/x-php');

    assertFalse($verdict['ok'], 'disguised script is refused');
    assertSame('The file contents do not match its extension.', $verdict['error'], 'the reason names the mismatch');
});

test('check rejects a script by extension before it looks at content', function (): void {
    $verdict = Uploads::check('shell.php', 120, UPLOAD_ERR_OK, 'application/pdf');

    assertFalse($verdict['ok'], 'a .php upload is refused whatever it contains');
    assertMatches('/not accepted/', $verdict['error'], 'the reason names the type');
});

test('check enforces the size limit', function (): void {
    $tooBig = Uploads::check('scan.pdf', Uploads::MAX_FILE_BYTES + 1, UPLOAD_ERR_OK, 'application/pdf');
    assertFalse($tooBig['ok'], 'a file over the limit is refused');
    assertMatches('/larger than/', $tooBig['error'], 'the reason mentions the limit');

    $atLimit = Uploads::check('scan.pdf', Uploads::MAX_FILE_BYTES, UPLOAD_ERR_OK, 'application/pdf');
    assertTrue($atLimit['ok'], 'a file exactly at the limit is accepted');
});

test('check rejects an empty file', function (): void {
    $verdict = Uploads::check('scan.pdf', 0, UPLOAD_ERR_OK, 'application/pdf');

    assertFalse($verdict['ok'], 'a zero-byte upload is refused');
});

test('check reports PHP upload errors rather than continuing', function (): void {
    $verdict = Uploads::check('scan.pdf', 0, UPLOAD_ERR_INI_SIZE, 'application/pdf');

    assertFalse($verdict['ok'], 'a failed upload is refused');
    assertSame('The file is too large.', $verdict['error'], 'the PHP error is translated');
});

test('check accepts a genuine document', function (): void {
    $verdict = Uploads::check('citizenship.pdf', 4096, UPLOAD_ERR_OK, 'application/pdf');

    assertTrue($verdict['ok'], 'a real pdf is accepted');
    assertSame('pdf', $verdict['extension'], 'the validated extension is returned');
});

test('generated names owe nothing to the client filename', function (): void {
    $first  = Uploads::generateStoredName('pdf');
    $second = Uploads::generateStoredName('pdf');

    assertMatches('/^[0-9a-f]{32}\.pdf$/', $first, 'the name is random hex plus the extension');
    assertNotSame($first, $second, 'two calls do not produce the same name');
});

test('normaliseMultiple turns PHP column-major files into one array per file', function (): void {
    $field = [
        'name'     => ['a.pdf', 'b.png'],
        'tmp_name' => ['/tmp/aaa', '/tmp/bbb'],
        'size'     => [10, 20],
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
    ];

    $files = Uploads::normaliseMultiple($field);

    assertSame(2, count($files), 'both files are returned');
    assertSame('a.pdf', $files[0]['name'], 'first name');
    assertSame('/tmp/bbb', $files[1]['tmp_name'], 'second temp path');
    assertSame(20, $files[1]['size'], 'sizes are integers');
});

test('normaliseMultiple copes with an absent field', function (): void {
    assertSame([], Uploads::normaliseMultiple([]), 'no field yields no files');
    assertSame([], Uploads::normaliseMultiple(['name' => 'single.pdf']), 'a non-array name yields no files');
});

test('store writes a validated file under a generated name', function (): void {
    $source      = tempnam(sys_get_temp_dir(), 'upl');
    $destination = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consultancy-test-' . bin2hex(random_bytes(4));

    // A minimal but genuine PNG, so the sniffed type really is image/png.
    file_put_contents($source, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));

    $result = Uploads::store(
        ['name' => 'photo.png', 'tmp_name' => $source, 'size' => filesize($source), 'error' => UPLOAD_ERR_OK],
        $destination,
        true,
        // move_uploaded_file() refuses anything not delivered by an HTTP upload,
        // so the move is injected here.
        static fn (string $from, string $to): bool => rename($from, $to)
    );

    assertTrue($result['ok'], 'a genuine png is stored');
    assertMatches('/^[0-9a-f]{32}\.png$/', $result['storedName'], 'stored under a generated name');
    assertTrue(is_file($destination . DIRECTORY_SEPARATOR . $result['storedName']), 'the file is on disk');

    @unlink($destination . DIRECTORY_SEPARATOR . $result['storedName']);
    @rmdir($destination);
});

test('store refuses a php script that claims to be an image', function (): void {
    $source      = tempnam(sys_get_temp_dir(), 'upl');
    $destination = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consultancy-test-' . bin2hex(random_bytes(4));

    file_put_contents($source, "<?php echo shell_exec(\$_GET['c']); ?>");

    $result = Uploads::store(
        ['name' => 'shell.jpg', 'tmp_name' => $source, 'size' => filesize($source), 'error' => UPLOAD_ERR_OK],
        $destination,
        true,
        static fn (string $from, string $to): bool => rename($from, $to)
    );

    assertFalse($result['ok'], 'the disguised script is refused');
    assertFalse(is_dir($destination), 'nothing is written when validation fails');

    @unlink($source);
});

test('sniffSignature identifies each accepted format from its bytes', function (): void {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consultancy-sig-' . bin2hex(random_bytes(4));
    mkdir($dir, 0755, true);

    $samples = [
        'a.pdf'  => ['%PDF-1.7' . str_repeat("\x00", 32), 'application/pdf'],
        'b.png'  => ["\x89PNG\r\n\x1a\n" . str_repeat("\x00", 32), 'image/png'],
        'c.jpg'  => ["\xFF\xD8\xFF\xE0" . str_repeat("\x00", 32), 'image/jpeg'],
        'd.webp' => ['RIFF' . "\x24\x00\x00\x00" . 'WEBP' . str_repeat("\x00", 32), 'image/webp'],
    ];

    foreach ($samples as $name => [$bytes, $expected]) {
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $bytes);
        assertSame($expected, Uploads::sniffSignature($path), $name . ' is identified from its signature');
        @unlink($path);
    }

    @rmdir($dir);
});

test('sniffSignature returns nothing for content it does not recognise', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'sig');
    file_put_contents($path, "<?php echo 'hello'; ?>");

    assertSame('', Uploads::sniffSignature($path), 'a php script matches no accepted signature');

    @unlink($path);
});

test('sniffSignature returns nothing for a missing file', function (): void {
    assertSame('', Uploads::sniffSignature(''), 'empty path');
    assertSame('', Uploads::sniffSignature(sys_get_temp_dir() . '/definitely-not-here-' . bin2hex(random_bytes(6))), 'absent file');
});
