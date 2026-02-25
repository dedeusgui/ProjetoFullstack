# Testing Rollout Progress

- Related Objectives: `OBJ-001`, `OBJ-002`, `OBJ-003`
- Current status: phase_2a_validated

## Completed

- PHPUnit config and suite split (`Unit`, `Action`)
- Test bootstrap and support helpers
- MySQL schema reset/import script and utilities
- Habit action handler extraction for first-wave testability
- Unit tests for scheduling/sanitizer/recommendation/support logic
- Action tests for habit create/update/toggle handlers
- PHP upgrade verified (`PHP 8.5.0`) and required CLI extensions enabled for Composer/PHPUnit/MySQL
- Composer dev dependencies installed and `vendor/bin/phpunit` available
- Full local validation succeeded:
  - `composer test:db:reset`
  - `composer test:unit`
  - `composer test:action`
  - `composer test`
- Action test flash assertions corrected to match handler-response testing style
- PHPUnit bootstrap warning removed (`$_SESSION` initialization)
- Phase 2A API/stats coverage scaffolding implemented:
  - API handler extraction for JSON endpoints (`stats`, `habits`) using `ActionResponse::json`
  - Shared API query-param normalizer (`view` / `scope`)
  - New PHPUnit unit test for API param normalization
  - New action/integration test files for API handlers, payload builders, `StatsQueryService`, and `HabitQueryService`
- Phase 2A validated locally with MySQL available:
  - `composer test:db:reset` -> OK
  - `composer test:action` -> OK (`44 tests`, `227 assertions`)
  - `composer test` -> OK (`69 tests`, `284 assertions`)
- Test infrastructure hardening during validation:
  - `TestDatabase::resetSchema()` now closes existing shared DB connection before drop/create
  - `SqlDumpImporter` ignores dump-level `CREATE DATABASE` / `USE` statements (reset already handles DB selection)
  - `SqlDumpImporter` now includes SQL preview context in import errors for faster debugging

## In Progress

- Coverage expansion continuation after Phase 2A validation (auth / remaining habits services)

## Blockers

- No active blocker for Phase 2A.
- Future blockers may appear in later phases (auth fixtures, additional action extraction, repository edge cases).

## Next Actions

1. Start `Phase 2B` auth coverage (service + login/register/logout action handler extraction/tests)
2. Keep DB-backed verification sequential (`composer test:db:reset` -> `composer test:action` -> `composer test`) because suites share `doitly_test`
3. Record outcomes in `docs/WORKLOG.md` and this file
