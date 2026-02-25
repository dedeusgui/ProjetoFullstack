# Runbook: Test Database Reset

## Purpose

Recreate the MySQL test schema used by action/integration tests.

## Default Configuration

- Test DB name: `doitly_test`
- Schema source: `sql/doitly_unified.sql`

## Command

```bash
composer test:db:reset
```

## What It Does

- Drops the test database if it exists
- Recreates it with utf8mb4 collation
- Imports the unified SQL dump
- Normalizes dump statements for test DB usage

## Preconditions

- MySQL/MariaDB is running
- User has permission to `CREATE DATABASE` / `DROP DATABASE`
- `vendor/autoload.php` exists (`composer install` was run)

## Troubleshooting

- DB connection error:
  - verify `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`
- Permission error:
  - use a DB user with create/drop privileges for the test DB
- SQL import failure:
  - check MariaDB/MySQL compatibility and dump statement reported by the script

