<?php

/**
 * Table definitions for the SQLite databases.
 *
 * These statements used to be inline in whichever endpoint happened to touch a
 * table first, which meant the same table was declared in several places and
 * could drift between them. Collecting them here gives one definition per
 * table and one place to correct when a constraint turns out to be wrong.
 *
 * Each method is idempotent, so calling it on an existing database does
 * nothing. Note the limit of that: CREATE TABLE IF NOT EXISTS will not alter a
 * table that already exists, so a definition corrected here reaches existing
 * databases only through migrate(), below.
 */
final class Schema
{
    public static function ensureClients(SQLite3 $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS clients (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                address TEXT NOT NULL,
                mobileNumber TEXT NOT NULL,
                email TEXT,
                gender TEXT DEFAULT 'Prefer Not Say' CHECK (gender IN ('Male', 'Female', 'Prefer Not Say')),
                bikeLicense TEXT DEFAULT 'no' CHECK (bikeLicense IN ('yes', 'no')),
                jobPreferences TEXT,
                documents TEXT,
                profilePic TEXT,
                visibility TEXT NOT NULL DEFAULT 'Normal' CHECK (visibility IN ('Normal', 'Archived', 'Deleted')),
                submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                remarks TEXT NULL
            )"
        );
    }

    /**
     * The jobs table.
     *
     * The default for visibility is 'Normal'. It read 'normal' until 2026-09-05,
     * which no insert could ever satisfy: the CHECK constraint on the same
     * column accepts only the capitalised spellings, so every attempt to add a
     * job without naming the column explicitly failed the constraint and was
     * reported to the user as "Failed to add job". Adding a job did not work at
     * all. See migrateJobsVisibilityDefault() for existing databases.
     */
    public static function ensureJobs(SQLite3 $db): void
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
                visibility TEXT NOT NULL DEFAULT 'Normal' CHECK (visibility IN ('Normal', 'Archived', 'Deleted')),
                submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                remarks TEXT NULL
            )"
        );
    }

    public static function ensureSubmittedEmails(SQLite3 $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS submitted_emails (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                submittedDate TEXT NOT NULL
            )"
        );
    }

    public static function ensureCredentials(SQLite3 $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS credentials (
                username TEXT PRIMARY KEY,
                hashed_secret TEXT NOT NULL
            )"
        );
    }

    /**
     * Applications and their payment state, joining a client to a job.
     */
    public static function ensurePaymentsAndStatus(SQLite3 $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS payments_and_status (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                UUID TEXT NOT NULL,
                jobID TEXT NOT NULL,
                applicationState TEXT NOT NULL DEFAULT 'Unknown' CHECK (applicationState IN ('Unknown', 'Applied', 'Interview', 'Accepted', 'Rejected')),
                paymentState TEXT NOT NULL DEFAULT 'Unknown' CHECK (paymentState IN ('Unknown', 'Received', 'Not Paid', 'Cancelled')),
                submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (UUID) REFERENCES clients(id),
                FOREIGN KEY (jobID) REFERENCES jobs(id)
            )"
        );
    }

    /**
     * The notifications database.
     *
     * A trigger copies each new notification into the queue that the SSE
     * endpoint reads, and notification_user_tracking records how far each
     * administrator has read.
     */
    public static function ensureNotifications(SQLite3 $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS notification_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                notification_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS notification_user_tracking (
                user_id TEXT PRIMARY KEY,
                last_seen_notification_id INTEGER DEFAULT 0
            );

            CREATE TRIGGER IF NOT EXISTS queue_new_notification
            AFTER INSERT ON notifications
            BEGIN
                INSERT INTO notification_queue (notification_id)
                VALUES (NEW.id);
            END;"
        );
    }

    /** Whether a table has a given column. */
    public static function columnExists(SQLite3 $db, string $table, string $column): bool
    {
        $result = $db->query(sprintf('PRAGMA table_info(%s)', $table));

        if ($result === false) {
            return false;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (($row['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the jobs table still carries the unusable lowercase default.
     *
     * Reads the stored CREATE statement, which is the only way SQLite exposes a
     * column default without attempting an insert.
     */
    public static function jobsVisibilityDefaultIsBroken(SQLite3 $db): bool
    {
        $sql = $db->querySingle(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'jobs'"
        );

        if (!is_string($sql) || $sql === '') {
            return false;
        }

        return str_contains($sql, "DEFAULT 'normal'");
    }

    /**
     * Rebuild the jobs table so its visibility default matches its own CHECK.
     *
     * SQLite cannot alter a column default in place, so the table is recreated
     * and the rows copied across inside a transaction. Existing rows keep their
     * visibility; any that somehow hold the lowercase value are corrected on the
     * way through, since that value is not one the application recognises.
     */
    public static function migrateJobsVisibilityDefault(SQLite3 $db): bool
    {
        if (!self::jobsVisibilityDefaultIsBroken($db)) {
            return false;
        }

        $db->exec('BEGIN IMMEDIATE');

        try {
            $db->exec('ALTER TABLE jobs RENAME TO jobs_legacy');

            self::ensureJobs($db);

            // remarks holds the reason recorded when a job is archived. It is
            // copied conditionally because a database created by an older
            // version of the add-job endpoint has no such column, while one
            // created by the seeder does; selecting it unconditionally would
            // fail on the former and dropping it would lose data on the latter.
            $hasRemarks = self::columnExists($db, 'jobs_legacy', 'remarks');

            $db->exec(
                'INSERT INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, deadline, skillPreferences, visibility, submittedAt, remarks)
                 SELECT id, companyName, address, mobileNumber, position, minimumSalary, deadline, skillPreferences,
                        CASE WHEN visibility IN (\'Normal\', \'Archived\', \'Deleted\') THEN visibility ELSE \'Normal\' END,
                        submittedAt,
                        ' . ($hasRemarks ? 'remarks' : 'NULL') . '
                 FROM jobs_legacy'
            );

            $db->exec('DROP TABLE jobs_legacy');
            $db->exec('COMMIT');
        } catch (Throwable $e) {
            $db->exec('ROLLBACK');

            throw $e;
        }

        return true;
    }
}
