# Credentials to rotate

Written 2026-09-05. Delete this file once the two items below are done.

## What happened

Four SQLite databases were committed to this repository:

| File | What it holds |
|---|---|
| `db/login.sqlite` | Admin usernames and bcrypt password hashes |
| `db/data.sqlite` | Client records: names, addresses, mobile numbers, emails |
| `db/waitlist.sqlite` | Email addresses submitted through the public form |
| `db/notifications.sqlite` | Notification history |

They have been removed from version control and added to `.gitignore`. The
files themselves are untouched on disk, so nothing was lost.

**This repository has a remote:** `github.com/nishantmajhi/consultancy.git`. If
that repository is public, or has ever been public, treat everything above as
disclosed. The data is still present in the git history regardless, because
removing a file in a new commit does not remove it from earlier ones.

## 1. Change the admin password

The hashes in `db/login.sqlite` are bcrypt via `password_hash()`, so they are
not reversible — but they are offline-crackable at leisure by anyone holding the
file, and the strength of that depends entirely on the password chosen.

Change it. There is no password-change screen, so reseed the credential:

```bash
php database/seed.php
```

`database/seeders/Admin.php` holds the seeded username and password; edit it
first, and do not commit a real password there either.

## 2. Decide about the git history

The databases remain readable in every commit before 2026-09-05. Two options:

- **Rewrite the history** with `git filter-repo` to remove them, then
  force-push. This changes every commit hash. It is the only way to actually
  remove the data from the repository.
- **Leave it and make the repository private**, if it is not already. Simpler,
  and adequate if the repository was never public.

This was deliberately left for you rather than done automatically, because
rewriting history is irreversible and force-pushing affects anything else that
has a copy.

## 3. Tell the people on the waitlist, if the repository was public

`db/waitlist.sqlite` holds email addresses that people gave you for a waitlist,
not for publication. If the repository was public, they should be told. This is
a judgement call and depends on when the repository became public relative to
when the addresses were collected.

## Not affected

`.env` holds only `COMPANY_NAME` and `DOMAIN_NAME`. Neither is secret and it
remains tracked deliberately; `.gitignore` explains why.
