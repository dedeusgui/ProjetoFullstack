# Runbook: Local Development Setup

## Purpose

Set up a local development environment capable of running the app and the automated tests.

## Prerequisites

- PHP CLI (target 8.2+)
- MySQL/MariaDB
- Composer
- Web server stack (XAMPP recommended for this project)

## Steps

1. Confirm PHP CLI version:
   - `php -v`
2. Confirm Composer version:
   - `composer --version`
3. Install dependencies:
   - `composer install`
4. Import app DB if needed:
   - `mysql -u <user> -p < sql/doitly_unified.sql`
5. Configure env vars (or defaults):
   - `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`
6. Confirm app bootstrap can load:
   - open a local page or run a minimal smoke check

## Notes

- Dev/test work requires Composer dev dependencies (including PHPUnit).
- If `composer install` fails due network restrictions, document the blocker in `docs/WORKLOG.md`.

