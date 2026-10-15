<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Jobs.php';

function validJobPost(array $overrides = []): array
{
    return array_merge([
        'name'             => 'Everest Traders',
        'address'          => 'Biratnagar',
        'mobileNumber'     => '9812345678',
        'position'         => 'Delivery Rider',
        'minimumSalary'    => '25000',
        'deadline'         => '2026-12-31',
        'skillPreferences' => ['Riding'],
    ], $overrides);
}

test('a complete job submission is accepted', function (): void {
    $result = Jobs::validateSubmission(validJobPost());

    assertTrue($result['ok'], 'the submission passes');
    assertSame('Everest Traders', $result['values']['companyName'], 'the company name comes from the name field');
    assertSame(25000, $result['values']['minimumSalary'], 'the salary is an integer');
});

test('required job fields are enforced', function (): void {
    assertFalse(Jobs::validateSubmission(validJobPost(['name' => '']))['ok'], 'company name');
    assertFalse(Jobs::validateSubmission(validJobPost(['address' => '']))['ok'], 'address');
    assertFalse(Jobs::validateSubmission(validJobPost(['position' => '']))['ok'], 'position');
    assertFalse(Jobs::validateSubmission(validJobPost(['mobileNumber' => 'abc']))['ok'], 'mobile number');
});

test('salary accepts what people type and rejects what is not a number', function (): void {
    assertSame(25000, Jobs::normaliseSalary('25,000'), 'thousands separators are removed');
    assertSame(25000, Jobs::normaliseSalary('25 000'), 'spaces are removed');
    assertSame(0, Jobs::normaliseSalary('0'), 'zero is a valid salary');
    assertSame(25000, Jobs::normaliseSalary(25000), 'an integer passes through');

    // Previously any of these was bound as SQLITE3_INTEGER and became 0.
    assertNull(Jobs::normaliseSalary('lots'), 'words are refused');
    assertNull(Jobs::normaliseSalary('-500'), 'negatives are refused');
    assertNull(Jobs::normaliseSalary('25.5'), 'decimals are refused');
    assertNull(Jobs::normaliseSalary(''), 'empty is refused');
    assertNull(Jobs::normaliseSalary(null), 'null is refused');
    assertNull(Jobs::normaliseSalary(Jobs::MAX_SALARY + 1), 'an implausible figure is refused');
});

test('the deadline must be a real date when supplied', function (): void {
    assertTrue(Jobs::isValidDate('2026-12-31'), 'a real date');
    assertFalse(Jobs::isValidDate('2026-02-30'), 'a day that does not exist');
    assertFalse(Jobs::isValidDate('31-12-2026'), 'the wrong order');
    assertFalse(Jobs::isValidDate('not a date'), 'not a date at all');

    assertTrue(Jobs::validateSubmission(validJobPost(['deadline' => '']))['ok'], 'an absent deadline is allowed');
    assertFalse(Jobs::validateSubmission(validJobPost(['deadline' => '2026-13-01']))['ok'], 'an impossible month is refused');
});

test('job identifiers include the salary band and stay unique', function (): void {
    $id = Jobs::generateId('Everest Traders', 25000, '250905-143012', 'ab12');

    assertSame('et_25_250905-143012-ab12', $id, 'initials, salary in thousands, timestamp, suffix');

    assertNotSame(
        Jobs::generateId('Everest Traders', 25000),
        Jobs::generateId('Everest Traders', 25000),
        'two jobs added in the same second get different identifiers'
    );
});
