# ADR-0003: PHPUnit + MySQL Test Schema Strategy

- Status: accepted
- Date: 2026-02-25

## Context

The project lacked an automated testing foundation. Action and service behavior depends on MySQL/MariaDB features (procedures, views, constraints), making pure mock-based tests insufficient for key flows.

## Decision

- Use PHPUnit as the test framework
- Split suites into `Unit` and `Action`
- Use a dedicated MySQL test schema (default `doitly_test`) for DB-backed tests
- Recreate/import schema from `sql/doitly_unified.sql` via test tooling

## Consequences

### Positive

- Realistic behavior for DB-backed flows
- Consistent local reset workflow (`composer test:db:reset`)
- Clear separation of fast unit tests and DB-backed action tests

### Negative / Cost

- Requires local MySQL/MariaDB availability
- Slower than SQLite-only or mocked integration testing
- Environment/version differences can affect stored procedures

## Alternatives Considered

- SQLite for integration tests
  - Rejected: poor parity with stored procedures and MySQL-specific behavior
- Unit tests only (no DB-backed action tests)
  - Rejected: misses critical end-to-end behavior for current risk areas

## Links

- `docs/architecture/testing-strategy.md`
- `scripts/test_db_reset.php`
- `tests/Support/TestDatabase.php`

