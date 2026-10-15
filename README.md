# Consultancy

An admin system for a job consultancy: it keeps the register of people looking
for work, the register of employers offering jobs, and the record of who was put
forward for what and whether the fee was paid.

Built in plain PHP with no framework and no Composer dependencies. The only
things it needs are PHP and SQLite.

---

## What it does

The business it models is a small placement agency. Someone walks in looking for
work; their details and documents are recorded. An employer calls with a
vacancy; that is recorded too. The agency matches one to the other, tracks the
application through to acceptance or rejection, and records whether the fee has
been collected.

That gives four things the software has to hold:

| Register | What a row is | Where it lives |
|---|---|---|
| Clients | A person looking for work, with their documents | `db/data.sqlite` |
| Jobs | A vacancy an employer is offering | `db/data.sqlite` |
| Applications | One client put forward for one job, with its state and payment state | `db/data.sqlite` |
| Waitlist | An email address left on the public site by someone not yet registered | `db/waitlist.sqlite` |

Clients and jobs are never deleted outright. They carry a `visibility` of
`Normal`, `Archived` or `Deleted`, so a record can be taken off the working list
and later restored, and archiving records the reason in `remarks`.

The public side of the site is a single page describing the company with a form
that collects an email address. Everything else is behind a sign-in.

---

## Running it

You need PHP 8.1 or newer with the `sqlite3` extension. `mbstring` is used when
present but is not required.

```sh
git clone https://github.com/nishantmajhi/consultancy.git
cd consultancy
```

Set the two values the site displays:

```ini
; .env
COMPANY_NAME = "Your Company"
DOMAIN_NAME  = "https://your-domain.example"
```

Create the databases and load sample data:

```sh
php database/seed.php
```

Then either:

```sh
php -S localhost:1016          # any free port
```

or:

```sh
docker compose up --detach     # serves on http://localhost:1016
docker compose stop
```

The sign-in credentials are the ones in `database/seeders/Admin.php`. Change
them before the site is reachable by anyone else.

---

## Layout

```
.
├── index.php              Public landing page
├── login/                 Sign-in
├── admin/                 The admin screens, one directory per screen
├── api/                   JSON endpoints, grouped by verb (add, fetch, search…)
├── auth/                  Session lifecycle and CORS headers
├── utils/                 Shared logic: validation, uploads, CSRF, search
├── db/
│   ├── Schema.php         Every table definition, one per table
│   └── *.sqlite           The databases themselves (never committed)
├── database/
│   ├── seed.php           Creates the databases and loads sample data
│   ├── data/              The sample data
│   └── seeders/           Writes that data into the databases
├── templates/             Shared page furniture
├── uploads/               Client documents and photographs (never committed)
└── tests/                 The test suite
```

Two conventions worth knowing before adding anything:

**Every table is defined once, in `db/Schema.php`.** Endpoints and seeders call
`Schema::ensureJobs($db)` rather than writing their own `CREATE TABLE`. This is
not tidiness for its own sake: the jobs table was previously declared in two
places with different definitions, and the disagreement between them meant
adding a job failed every time for months without anyone noticing, because the
seeder took a different path through the code than the endpoint did.

**Every API endpoint starts with the same four lines.**

```php
Session::init();
Api::begin();
Api::requireAuth();
Api::requireMethod('POST');
Api::requireCsrf();
```

`utils/Api.php` owns what those mean. Endpoints previously each carried their
own copy of these checks, and they had drifted: some answered HTTP 200 with an
error body, some returned the raw exception message to the caller, and one had
no authentication check at all.

---

## Testing

```sh
php tests/run.php           # everything
php tests/run.php Uploads   # just the files whose name contains "Uploads"
```

There is no test framework and none is wanted. `tests/Harness.php` is about two
hundred lines and gives `test()`, `assertSame()` and friends, which is enough
for what is being tested here and means the suite runs on any machine with PHP
and nothing else installed.

What is covered is the logic that is both easy to get wrong and expensive to get
wrong: upload validation, form validation, CSRF comparison, redirect targets,
session expiry, and the schema. The screens themselves are not covered.

---

## Security notes

These are the decisions a reader should not undo by accident.

**Uploads are validated on two axes and renamed.** `utils/Uploads.php` requires
that the extension is on a short allowlist *and* that the file's leading bytes
identify it as that type. Checking one without the other is bypassable in both
directions: an extension check alone accepts a PHP script called `x.jpg`, and a
content check alone accepts a real GIF called `x.php`. Files are then stored
under a generated random name, never the one the browser supplied.

`uploads/.htaccess` disables script execution in that directory as a second
layer. Both matter, because the upload directory is inside the document root and
Apache serves it.

Adding a file type to that allowlist is not a one-line change. Anything a
browser will execute — HTML, SVG — would need the files served as attachments or
from a separate origin first.

**File contents are identified by signature, not by `mime_content_type()`.** The
signature check is deterministic across machines and works without
`ext-fileinfo`, which not every PHP build has. Calling a function that does not
exist is a fatal error the `@` operator cannot suppress, which is how the first
version of this failed.

**Every state-changing endpoint checks a CSRF token**, compared with
`hash_equals()`. Endpoints using `PATCH`, `PUT` and `DELETE` are already shielded
from cross-origin submission by the browser's preflight, but that is a property
of the browser rather than of this application.

**Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS.**
Sessions expire both on inactivity and at an absolute age.

**`?redirect=` is confined to this site** by `utils/Redirect.php`. Without that
check the sign-in page is an open redirect, which is how a phishing link gets to
begin with a domain the reader trusts.

---

## Known gaps

- **No rate limiting on sign-in.** There is one shared admin account and
  unlimited attempts against it. This is the most significant thing still
  missing.
- **No per-user accounts.** Everyone signs in as the same administrator, so
  nothing records which person archived a client or marked a fee as paid.
- **`.env` is read with `parse_ini_file` in fourteen separate files.** It works,
  but a missing `.env` produces an unhelpful failure in each of them.
- **`gen/cv.php` and `gen/mou.php` are reachable without signing in.** They
  render an empty template rather than client data, so nothing leaks as it stands, but
  they should be behind the same check as everything else.
- The databases are SQLite files written on every request, which is fine for one
  office and would not survive being deployed twice against shared storage.

## Licence

Free for personal, non-commercial use. You may use, study and modify this
software for yourself, but you may not use it commercially, and you may not
share or publish it, in its original form or modified. See [LICENSE](LICENSE)
for the full terms.
