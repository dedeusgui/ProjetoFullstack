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

## Coverage Expansion Status

- `OBJ-003` coverage expansion (Phase 2A-2F) is completed and locally validated.
- Detailed rollout execution evidence and final counts are tracked in `docs/features/testing-rollout/progress.md`.
- The original phase plan remains in `docs/features/testing-rollout/coverage-expansion-plan.md` as a historical planning artifact.

## Next Testing Focus

1. Continue extracting procedural actions into handlers where testability is poor (`OBJ-005`)
2. Introduce CI automation for the validated local test workflow (at least `composer test`; optionally `composer qa`)
3. Backfill only high-value remaining branches discovered during feature work (avoid low-value exhaustive helper/runtime path tests)
