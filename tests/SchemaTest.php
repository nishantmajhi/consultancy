<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

/*
 * These are the regression tests for the bug that stopped anyone from adding a
 * job: the jobs table declared DEFAULT 'normal' on a column whose CHECK
 * constraint accepts only 'Normal', 'Archived' or 'Deleted', so an insert that
 * did not name the column failed the constraint every time. The failure was
 * reported to the user as "Failed to add job" with no further detail.
 */

/** The exact table definition as it stood before the fix. */
function createLegacyJobsTable(SQLite3 $db): void
{
    $db->exec(
        "CREATE TABLE IF NOT EXISTS jobs (
            id TEXT PRIMARY KEY,
            companyName TEXT NOT NULL,
            address TEXT NOT NULL,
            mobileNumber TEXT NOT NULL,
            position TEXT NOT NULL,
            minimumSalary INTEGER NOT NULL,
            deadline TEXT,
            skillPreferences TEXT,
            visibility TEXT CHECK(visibility IN ('Normal', 'Archived', 'Deleted')) DEFAULT 'normal',
            submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );
}

function insertJobWithoutVisibility(SQLite3 $db, string $id): void
{
    $stmt = $db->prepare(
        'INSERT INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, deadline, skillPreferences)
         VALUES (:id, :companyName, :address, :mobileNumber, :position, :minimumSalary, :deadline, :skillPreferences)'
    );

    $stmt->bindValue(':id', $id, SQLITE3_TEXT);
    $stmt->bindValue(':companyName', 'Everest Traders', SQLITE3_TEXT);
    $stmt->bindValue(':address', 'Biratnagar', SQLITE3_TEXT);
    $stmt->bindValue(':mobileNumber', '9812345678', SQLITE3_TEXT);
    $stmt->bindValue(':position', 'Delivery Rider', SQLITE3_TEXT);
    $stmt->bindValue(':minimumSalary', 25000, SQLITE3_INTEGER);
    $stmt->bindValue(':deadline', '2026-12-31', SQLITE3_TEXT);
    $stmt->bindValue(':skillPreferences', '[]', SQLITE3_TEXT);
    $stmt->execute();
}

test('the old jobs table really could not accept an insert', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    createLegacyJobsTable($db);

    Harness::assertThrows(
        static fn () => insertJobWithoutVisibility($db, 'legacy-1'),
        'the pre-fix schema rejects every insert that omits visibility'
    );

    $db->close();
});

test('the corrected jobs table accepts an insert that omits visibility', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    Schema::ensureJobs($db);
    insertJobWithoutVisibility($db, 'fixed-1');

    assertSame(
        'Normal',
        $db->querySingle("SELECT visibility FROM jobs WHERE id = 'fixed-1'"),
        'the default is a value the CHECK constraint accepts'
    );

    $db->close();
});

test('a legacy database is detected and repaired', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    createLegacyJobsTable($db);

    // A row that predates the fix, inserted the only way the old schema allowed.
    $db->exec(
        "INSERT INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, visibility)
         VALUES ('old-1', 'Old Co', 'Somewhere', '9800000000', 'Porter', 15000, 'Archived')"
    );

    assertTrue(Schema::jobsVisibilityDefaultIsBroken($db), 'the broken default is detected');
    assertTrue(Schema::migrateJobsVisibilityDefault($db), 'the migration reports that it ran');
    assertFalse(Schema::jobsVisibilityDefaultIsBroken($db), 'the default is corrected afterwards');

    assertSame('Archived', $db->querySingle("SELECT visibility FROM jobs WHERE id = 'old-1'"), 'existing rows keep their visibility');
    assertSame('Old Co', $db->querySingle("SELECT companyName FROM jobs WHERE id = 'old-1'"), 'existing data survives the rebuild');

    insertJobWithoutVisibility($db, 'after-migration');
    assertSame('Normal', $db->querySingle("SELECT visibility FROM jobs WHERE id = 'after-migration'"), 'inserts work after the migration');

    $db->close();
});

test('the migration is a no-op on an already-correct database', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    Schema::ensureJobs($db);

    assertFalse(Schema::jobsVisibilityDefaultIsBroken($db), 'a fresh table is not flagged');
    assertFalse(Schema::migrateJobsVisibilityDefault($db), 'the migration declines to run twice');

    $db->close();
});

test('the clients table accepts a row with defaults applied', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    Schema::ensureClients($db);

    $db->exec(
        "INSERT INTO clients (id, name, address, mobileNumber) VALUES ('c1', 'Sita Rai', 'Biratnagar', '9812345678')"
    );

    assertSame('Normal', $db->querySingle("SELECT visibility FROM clients WHERE id = 'c1'"), 'visibility defaults correctly');
    assertSame('Prefer Not Say', $db->querySingle("SELECT gender FROM clients WHERE id = 'c1'"), 'gender defaults correctly');
    assertSame('no', $db->querySingle("SELECT bikeLicense FROM clients WHERE id = 'c1'"), 'bike licence defaults correctly');

    $db->close();
});

test('the jobs table carries every column the endpoints write to', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    Schema::ensureJobs($db);

    // api/archive/job.php writes remarks when a job is archived. A definition
    // without that column produces "no such column" only at archive time, long
    // after the table was created.
    foreach (['id', 'companyName', 'address', 'mobileNumber', 'position',
              'minimumSalary', 'deadline', 'skillPreferences', 'visibility',
              'submittedAt', 'remarks'] as $column) {
        assertTrue(Schema::columnExists($db, 'jobs', $column), 'jobs.' . $column . ' exists');
    }

    $db->exec("INSERT INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, visibility)
               VALUES ('j1', 'Acme', 'Here', '9800000000', 'Porter', 1000, 'Normal')");
    $db->exec("UPDATE jobs SET visibility = 'Archived', remarks = 'position filled' WHERE id = 'j1'");

    assertSame('position filled', $db->querySingle("SELECT remarks FROM jobs WHERE id = 'j1'"), 'archiving records a reason');

    $db->close();
});

test('the clients table carries every column the endpoints write to', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    Schema::ensureClients($db);

    foreach (['id', 'name', 'address', 'mobileNumber', 'email', 'gender',
              'bikeLicense', 'jobPreferences', 'documents', 'profilePic',
              'visibility', 'submittedAt', 'remarks'] as $column) {
        assertTrue(Schema::columnExists($db, 'clients', $column), 'clients.' . $column . ' exists');
    }

    $db->close();
});

test('the migration preserves archived reasons', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    // The shape the seeder creates: broken default, but with a remarks column.
    $db->exec(
        "CREATE TABLE jobs (
            id TEXT PRIMARY KEY,
            companyName TEXT NOT NULL,
            address TEXT NOT NULL,
            mobileNumber TEXT NOT NULL,
            position TEXT NOT NULL,
            minimumSalary INTEGER NOT NULL,
            deadline TEXT,
            skillPreferences TEXT,
            visibility TEXT CHECK(visibility IN ('Normal', 'Archived', 'Deleted')) DEFAULT 'normal',
            submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            remarks TEXT NULL
        )"
    );

    $db->exec("INSERT INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, visibility, remarks)
               VALUES ('j1', 'Acme', 'Here', '9800000000', 'Porter', 1000, 'Archived', 'position filled')");

    assertTrue(Schema::migrateJobsVisibilityDefault($db), 'the migration runs');

    assertSame('position filled', $db->querySingle("SELECT remarks FROM jobs WHERE id = 'j1'"), 'the reason survives the rebuild');
    assertSame('Archived', $db->querySingle("SELECT visibility FROM jobs WHERE id = 'j1'"), 'visibility survives');

    $db->close();
});

test('the migration copes with a legacy table that has no remarks column', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    // The shape the old add-job endpoint created: no remarks column at all.
    createLegacyJobsTable($db);
    $db->exec("INSERT INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, visibility)
               VALUES ('j1', 'Acme', 'Here', '9800000000', 'Porter', 1000, 'Normal')");

    assertFalse(Schema::columnExists($db, 'jobs', 'remarks'), 'the legacy table has no remarks column');
    assertTrue(Schema::migrateJobsVisibilityDefault($db), 'the migration runs');
    assertTrue(Schema::columnExists($db, 'jobs', 'remarks'), 'the column is added');
    assertNull($db->querySingle("SELECT remarks FROM jobs WHERE id = 'j1'"), 'existing rows get a null remark');
    assertSame('Acme', $db->querySingle("SELECT companyName FROM jobs WHERE id = 'j1'"), 'the row survives');

    $db->close();
});
