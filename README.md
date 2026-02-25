# Doitly

Habit tracking web application with gamification, progress metrics, and an incremental layered PHP architecture.

This repository keeps engineering documentation centralized in `docs/`. The root `README.md` is intentionally GitHub-facing (product overview + quickstart + links).

## Highlights

- Habit CRUD with scheduling and completion tracking
- Dashboard and historical progress views
- Gamification (XP, achievements, streaks)
- Layered architecture (`public` -> `actions` -> `app` -> repositories)
- PHPUnit test foundation with MySQL test reset workflow

## Tech Stack

- PHP 8.2+
- MySQL / MariaDB (MySQLi)
- Bootstrap 5 + vanilla JavaScript
- PHPUnit 10 (dev)

## Quickstart (Local)

### Prerequisites

- PHP 8.2+
- MySQL or MariaDB
- Composer
- Apache/XAMPP (recommended for local web serving)

### Install

```bash
composer install
```

### Database

Import the schema snapshot:

```bash
mysql -u root -p < sql/doitly_unified.sql
```

Default connection settings are read from environment variables (with local fallbacks in `config/database.php`).

### Run (XAMPP example)

Place the project under `htdocs/` and open:

```text
http://localhost/ProjetoFullstack/public/
```

## Tests (Quick)

Use a dedicated test database (default `doitly_test`) and run:

```bash
composer test:db:reset
composer test
```

Additional commands:

```bash
composer test:unit
composer test:action
composer qa
```

## Engineering Docs (Canonical)

Start here for architecture, standards, ADRs, runbooks, and feature progress:

- `docs/README.md` (hub)
- `docs/STATUS.md` (current state)
- `docs/standards/engineering-handbook.md` (clean code / SOLID / review / verification policy)
- `docs/architecture/system-architecture.md` (architecture boundaries and refactor direction)

## Contributing

Contributions are welcome. For engineering workflow and handoff expectations, use:

- `docs/CONTRIBUTING_DEV.md`

Use Conventional Commits when possible.

## Authors

- Ismael Gomes (Rex)
- Guilherme de Deus
