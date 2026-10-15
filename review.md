# Code Review — Consultancy

Original review 2026-09-04. **Updated 2026-09-05 after the fixes on branch
`cleanup/2026-09-05` (two commits, unpushed).**

## Status

| # | Finding | State |
|---|---|---|
| 1 | Arbitrary file upload into a PHP-executing directory (remote code execution) | **Fixed** |
| 2 | Adding a job failed every time: schema `DEFAULT 'normal'` violated its own `CHECK` | **Fixed** |
| 3 | `api/email/remove.php` had no authentication check at all | **Fixed** |
| 4 | Twenty endpoints returned `$e->getMessage()` to the caller | **Fixed** |
| 5 | Unauthorised responses used HTTP 200 with an error body | **Fixed** |
| 6 | Open redirect via `login/?redirect=` | **Fixed** |
| 7 | CSRF tokens compared with `!==` rather than `hash_equals` | **Fixed** |
| 8 | Session cookies lacked `HttpOnly`, `SameSite`, `Secure`; no absolute lifetime | **Fixed** |
| 9 | Four SQLite databases with real personal data committed | **Untracked** — see `SECRETS-TODO.md` |
| 10 | Uploads stored absolute filesystem paths; documents never displayed | **Fixed** |
| 11 | Client and job IDs collided within the same second | **Fixed** |
| 12 | Files orphaned on disk when the insert failed | **Fixed** |
| 13 | `mkdir(0777)` | **Fixed** — now 0755 |
| 14 | No input validation before the database | **Fixed** |
| 15 | `console.log` of full client and job forms | **Removed** |
| 16 | `test/` vs `tests/` confusion | **Fixed** — now `database/` and `tests/` |
| 17 | No rate limiting on sign-in | **Open** |
| 18 | `gen/cv.php`, `gen/mou.php` reachable unauthenticated | **Open** |
| 19 | `.env` read by `parse_ini_file` in 14 files | **Open** |
| 20 | Single shared admin account, no attribution | **Open** |

Two further defects were found and fixed while working, both introduced by the
fix itself and caught by tests written before the code:

- `Schema::ensureJobs()` omitted the `remarks` column that `api/archive/job.php`
  writes to, which would have failed only at archive time.
- The visibility migration would have dropped `remarks` when rebuilding the
  table, losing every recorded archive reason.

## What to check first

1. **`SECRETS-TODO.md`** — the admin password and the git history are yours to
   decide on. The repository has a GitHub remote, so if it is or was public, the
   client records and waitlist addresses are disclosed.
2. **`admin/listClients/viewClient.js`** — this is the one change that alters
   what appears on screen. Client documents and profile photographs now display
   instead of placeholders. Layout, styling and interaction are untouched. Two
   lines; revert them if you disagree.
3. **Run it**: `php database/seed.php` then `php -S localhost:1016`, and try
   adding a job. That has never worked before.

## The remaining open items, in the order I would do them

**Rate limiting on sign-in** is the significant one. One shared password with
unlimited attempts is the weakest point left. A `login_attempts` table keyed by
username and IP, with an escalating delay, fits the existing SQLite setup.

**Put `gen/cv.php` and `gen/mou.php` behind `Api::requireAuth()`.** They
currently render an empty template so nothing leaks, but they are the only
unauthenticated pages in the admin area and that will not stay true.

**Centralise `.env` loading.** Fourteen files call `parse_ini_file` and index
the result directly; a missing or malformed file produces a different unhelpful
error in each. One `Env::get()` helper with defaults would fix it. Deliberately
left alone because it touches every page renderer and I could not exercise those
screens while you were away.

**Per-user accounts.** The ledger records what changed but not who changed it,
which for a system holding client fee records is worth having.

## Test suite

`php tests/run.php` — 69 tests, 232 assertions, no dependencies. Covers upload
validation, client and job validation, CSRF, redirect safety, session expiry,
the schema, and an end-to-end add-job flow against a temporary database.

The upload tests are deliberately weighted towards rejection cases. If you add a
file type to the allowlist, `tests/UploadsTest.php` is where to prove it behaves.

## Licence, 2026-10-02

The author chose one licence for all of their projects: free for personal,
non-commercial use, modifiable for the user's own use, and not to be
distributed, original or modified. No standard licence says that, so `LICENSE`
is a short custom text, the same in every project apart from the year and what
it excludes. The README's licence section says the same. Anything above that
calls the licence an open question, or calls the project MIT, is superseded.

## CI, 2026-10-02

`.github/workflows/ci.yml` runs `tests/run.php` on PHP 8.4 with SQLite3 and
mbstring, on `ubuntu-latest`. Nothing has been pushed, so it has not run on
GitHub yet. Every job was run before committing, from a fresh clone of this
branch, the way a runner would run it: Linux jobs in a throwaway Ubuntu 24.04
(the image `ubuntu-latest` uses) with the same tool versions. 69 tests, 232
assertions.
