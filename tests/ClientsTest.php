<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Clients.php';

/** A submission with every field valid, for tests that vary one thing at a time. */
function validClientPost(array $overrides = []): array
{
    return array_merge([
        'name'           => 'Sita Rai',
        'address'        => 'Biratnagar',
        'mobileNumber'   => '9812345678',
        'email'          => 'sita@example.com',
        'gender'         => 'Female',
        'bikeLicense'    => 'yes',
        'jobPreferences' => ['Driver'],
    ], $overrides);
}

test('a complete submission is accepted', function (): void {
    $result = Clients::validateSubmission(validClientPost());

    assertTrue($result['ok'], 'the submission passes');
    assertSame('Sita Rai', $result['values']['name'], 'the name is returned trimmed');
    assertSame('9812345678', $result['values']['mobileNumber'], 'the number is normalised to digits');
});

test('the name is required and bounded', function (): void {
    assertFalse(Clients::validateSubmission(validClientPost(['name' => '']))['ok'], 'empty name');
    assertFalse(Clients::validateSubmission(validClientPost(['name' => '   ']))['ok'], 'whitespace-only name');

    $tooLong = str_repeat('a', Clients::MAX_NAME_LENGTH + 1);
    assertFalse(Clients::validateSubmission(validClientPost(['name' => $tooLong]))['ok'], 'over-long name');
});

test('the address is required', function (): void {
    assertFalse(Clients::validateSubmission(validClientPost(['address' => '']))['ok'], 'empty address');
});

test('mobile numbers accept the punctuation people type', function (): void {
    assertSame('9812345678', Clients::normaliseMobileNumber('98-1234 5678'), 'dashes and spaces are stripped');
    assertSame('9779812345678', Clients::normaliseMobileNumber('+977 9812345678'), 'a country code is kept as digits');
    assertSame('9812345678', Clients::normaliseMobileNumber('(981) 234-5678'), 'brackets are stripped');
});

test('mobile numbers reject anything that is not a number', function (): void {
    assertNull(Clients::normaliseMobileNumber(''), 'empty');
    assertNull(Clients::normaliseMobileNumber('not a phone'), 'letters');
    assertNull(Clients::normaliseMobileNumber('12345'), 'too short');
    assertNull(Clients::normaliseMobileNumber(str_repeat('9', 16)), 'too long');
    assertNull(Clients::normaliseMobileNumber('9812345678; DROP TABLE clients'), 'trailing sql');
});

test('the email is optional but must be valid when given', function (): void {
    assertTrue(Clients::validateSubmission(validClientPost(['email' => '']))['ok'], 'an absent email is allowed');
    assertFalse(Clients::validateSubmission(validClientPost(['email' => 'not-an-email']))['ok'], 'a malformed email is refused');
});

test('gender and bike licence must match the database constraints', function (): void {
    foreach (Clients::GENDERS as $gender) {
        assertTrue(Clients::validateSubmission(validClientPost(['gender' => $gender]))['ok'], $gender . ' is accepted');
    }

    // Previously this reached SQLite and came back as a bare constraint failure.
    assertFalse(Clients::validateSubmission(validClientPost(['gender' => 'Other']))['ok'], 'an unknown gender is refused early');
    assertFalse(Clients::validateSubmission(validClientPost(['bikeLicense' => 'maybe']))['ok'], 'an unknown licence value is refused');
});

test('gender defaults to the schema default when absent', function (): void {
    $post = validClientPost();
    unset($post['gender']);

    $result = Clients::validateSubmission($post);

    assertTrue($result['ok'], 'an absent gender is allowed');
    assertSame('Prefer Not Say', $result['values']['gender'], 'it falls back to the schema default');
});

test('at least one job preference is required', function (): void {
    assertFalse(Clients::validateSubmission(validClientPost(['jobPreferences' => []]))['ok'], 'an empty list is refused');

    $post = validClientPost();
    unset($post['jobPreferences']);
    assertFalse(Clients::validateSubmission($post)['ok'], 'an absent list is refused');
});

test('job preferences are cleaned rather than trusted', function (): void {
    assertSame(['Driver'], Clients::normaliseJobPreferences('Driver'), 'a bare string becomes a one-item list');
    assertSame(['Driver', 'Cook'], Clients::normaliseJobPreferences([' Driver ', 'Cook']), 'values are trimmed');
    assertSame(['Driver'], Clients::normaliseJobPreferences(['Driver', 'Driver']), 'duplicates are removed');
    assertSame(['Driver'], Clients::normaliseJobPreferences(['Driver', '', '   ']), 'blank entries are dropped');
    assertSame([], Clients::normaliseJobPreferences(null), 'null yields nothing');
    assertSame([], Clients::normaliseJobPreferences([['nested']]), 'non-scalar entries are dropped');
});

test('identifiers are built from initials and are unique', function (): void {
    $id = Clients::generateId('Sita Rai', '250905-143012', 'ab12');

    assertSame('sr_250905-143012-ab12', $id, 'initials, timestamp and suffix');

    // The suffix is what prevents the collision that previously caused a failed
    // insert after the uploaded files had already been written to disk.
    assertNotSame(
        Clients::generateId('Sita Rai'),
        Clients::generateId('Sita Rai'),
        'two clients added in the same second get different identifiers'
    );
});

test('identifiers never contain characters that mean something to a filesystem', function (): void {
    // The identifier becomes a directory name under uploads/.
    $awkward = Clients::generateId('../.. /etc/passwd', '250905-143012', 'ab12');

    assertMatches('/^[a-z0-9_.-]+$/u', $awkward, 'only safe characters survive');

    $empty = Clients::generateId('!!! ???', '250905-143012', 'ab12');
    assertSame('client_250905-143012-ab12', $empty, 'a name with no letters falls back to a fixed prefix');
});

test('identifiers cope with non-latin names', function (): void {
    $id = Clients::generateId('सीता राई', '250905-143012', 'ab12');

    assertMatches('/_250905-143012-ab12$/', $id, 'the suffix is intact');
    assertNotSame('client_250905-143012-ab12', $id, 'devanagari initials are kept rather than discarded');
});

test('discardFiles removes what was written and tolerates what was not', function (): void {
    $present = tempnam(sys_get_temp_dir(), 'dis');
    $absent  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'not-here-' . bin2hex(random_bytes(4));

    Clients::discardFiles([$present, $absent, '']);

    assertFalse(is_file($present), 'the written file is removed');
});
