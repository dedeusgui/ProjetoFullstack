# Project Overview (Development Context)

## What This Project Is

Doitly is a PHP + MySQL web application for habit tracking with gamification and recommendation features.

## Current Stack (Observed)

- PHP (project targets modern PHP; currently using Composer and MySQLi)
- MySQL / MariaDB
- Composer autoload (PSR-4 `App\\`)
- Procedural HTTP entrypoints in `public/` and `actions/`
- Layered app code in `app/`
- SQL schema dump in `sql/doitly_unified.sql`

## Repository Layout (High Level)

- `public/`: page entrypoints and static assets
- `actions/`: mutation and API entrypoints
- `app/`: domain/application services, repositories, support utilities
- `config/`: bootstrap, DB connection, helpers, error/security/auth helpers
- `sql/`: unified schema/procedures/views dump
- `tests/`: PHPUnit unit/action tests and shared test support
- `docs/`: development docs and progress tracking (this system)

## Current Engineering Focus

- Stabilize local environment after PHP upgrade
- Validate newly added automated tests
- Expand test coverage to more critical flows after the first-wave habit coverage

