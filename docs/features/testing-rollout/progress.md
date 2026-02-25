# Testing Rollout Progress

- Related Objectives: `OBJ-001`, `OBJ-002`, `OBJ-003`
- Current status: phase_2b_validated

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
- Phase 2B auth coverage implemented and validated locally:
  - extracted handler-based auth actions for login/register/logout using `ActionResponse`
  - added DB-backed `AuthService` integration tests
  - added auth action handler tests for validation, rate limit, login success/failure, register branches, and logout session clear
  - `composer test:db:reset` -> OK
  - `composer test:action` -> OK (`68 tests`, `317 assertions`)
  - `composer test` -> OK (`93 tests`, `374 assertions`)
  - `composer qa` -> OK (`25 tests`, `57 assertions`)
- Test infrastructure hardening during validation:
  - `TestDatabase::resetSchema()` now closes existing shared DB connection before drop/create
  - `SqlDumpImporter` ignores dump-level `CREATE DATABASE` / `USE` statements (reset already handles DB selection)
  - `SqlDumpImporter` now includes SQL preview context in import errors for faster debugging

## In Progress

- Coverage expansion continuation after Phase 2B validation (remaining habits services / profile-export / repository-support slices)

## Blockers

- No active blocker for Phase 2B.
- Future blockers may appear in later phases (additional action extraction, repository edge cases, export/CSV harness needs).

## Next Actions

1. Start `Phase 2C` habit command/completion/access service and delete/archive action coverage
2. Keep DB-backed verification sequential (`composer test:db:reset` -> `composer test:action` -> `composer test`) because suites share `doitly_test`
3. Record outcomes in `docs/WORKLOG.md` and this file
