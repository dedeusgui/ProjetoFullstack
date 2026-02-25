# Testing Strategy (Current)

## Chosen Approach

- Framework: PHPUnit
- Test layers:
  - `Unit` for pure logic and deterministic utility/service behavior
  - `Action` for handler-level behavior with MySQL-backed integration
- DB strategy for action tests: dedicated MySQL test schema (`doitly_test` by default)

## Implemented Foundations

- `phpunit.xml` with `Unit` and `Action` suites
- `tests/bootstrap.php`
- `tests/Support/TestDatabase.php` for schema reset and DB access
- `tests/Support/SqlDumpImporter.php` for importing `sql/doitly_unified.sql`
- `tests/Support/FixtureLoader.php` and request-state helpers

## First-Wave Coverage (Implemented)

- Unit tests:
  - habit scheduling policy
  - habit input sanitizer
  - time-of-day mapping
  - recommendation score/trend logic
  - referer redirect resolver
- Action tests:
  - habit create/update/toggle handlers

## Current Validation Status

- Local environment validation completed on `2026-02-25`
- Verified runtime/tooling baseline:
  - `PHP 8.5.0` CLI
  - Composer dev dependencies installed (`vendor/bin/phpunit` available)
- Verified commands:
  - `composer test:db:reset`
  - `composer test:unit`
  - `composer test:action`
  - `composer test`

## Next Testing Milestones

1. Expand tests to auth/API/stats flows (`OBJ-003`)
2. Continue extracting procedural actions into handlers where testability is poor
3. Track coverage rollout progress in `docs/features/testing-rollout/coverage-expansion-plan.md`
