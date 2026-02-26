# Testing Rollout Progress

- Related Objectives: `OBJ-001`, `OBJ-002`, `OBJ-003`
- Current status: phase_2f_validated

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
- Phase 2C habits coverage implemented and validated locally:
  - extracted handler-based delete/archive habit actions using `ActionResponse`
  - added DB-backed tests for `HabitCommandService`, `HabitCompletionService`, and `HabitAccessService`
  - added action handler tests for delete (`habit_id`/`id`) and archive/restore operation dispatch
  - representative completion-service snapshot invalidation checks added (`user_recommendations`)
  - `composer test:db:reset` -> OK
  - `composer test:action` -> OK (`88 tests`, `388 assertions`)
  - `composer test` -> OK (`113 tests`, `445 assertions`)
  - `composer qa` -> OK (`25 tests`, `57 assertions`)
- Phase 2D profile/settings/export coverage implemented and validated locally:
  - extracted profile/update/reset/export actions into handlers with `ActionResponse`
  - added CSV response support to `ActionResponse` / `actionApplyResponse(...)` for testable export actions
  - extracted CSV generation/query orchestration into `UserDataCsvExportService`
  - added DB-backed tests for `ProfileService` validation/success/rollback/reset branches
  - added action handler tests for update/reset return-path and session behavior
  - added export CSV handler tests (unauthorized, user-missing, empty sections, representative summary row)
  - `composer test:db:reset` -> OK
  - `composer test:action` -> OK (`109 tests`, `457 assertions`)
  - `composer test` -> OK (`134 tests`, `514 assertions`)
  - `composer qa` -> OK (`25 tests`, `57 assertions`)
- Phase 2E repository/support/recommendation/achievement/progress coverage implemented and validated locally:
  - added repository contract tests (`Category`, `User`, `UserSettings`, `Habit`, `HabitQuery`, `Stats`)
  - added support/value-object tests (`ActionResponse`, `DateFormatter`, `RequestContext`, `UserLocalDateResolver`)
  - added representative service tests for `BehaviorAnalyzer`, `RecommendationEngine`, `AchievementService`, and `UserProgressService`
  - `composer test:db:reset` -> OK
  - `composer test:action` -> OK (`133 tests`, `559 assertions`)
  - `composer test` -> OK (`171 tests`, `653 assertions`)
  - `composer qa` -> OK (`38 tests`, `94 assertions`)
- Phase 2F helper/legacy helper coverage implemented and validated locally:
  - added helper tests for stable `config/*` global functions (`auth`, `security`, `error`, `action`, `app_helpers`)
  - added DB-backed integration tests for helper wrappers (`getAuthenticatedUserRecord`, category/achievement/progress wrappers)
  - intentionally avoided brittle direct tests for `header()/exit` helper paths; covered surrounding behavior via extracted handlers and helper state functions
  - `composer test:db:reset` -> OK
  - `composer test:action` -> OK (`137 tests`, `579 assertions`)
  - `composer test` -> OK (`195 tests`, `737 assertions`)
  - `composer qa` -> OK (`58 tests`, `158 assertions`)
- Test infrastructure hardening during validation:
  - `TestDatabase::resetSchema()` now closes existing shared DB connection before drop/create
  - `SqlDumpImporter` ignores dump-level `CREATE DATABASE` / `USE` statements (reset already handles DB selection)
  - `SqlDumpImporter` now includes SQL preview context in import errors for faster debugging

## In Progress

- Phase 2 rollout complete; track only residual low-priority branch gaps and future testability refactors as part of other objectives

## Blockers

- No active blocker for the Phase 2 coverage rollout.
- Residual gaps are mostly helper/bootstrap `header()/exit` paths and runtime-specific branches that may require additional extraction or subprocess-style tests.

## Next Actions

1. Keep `OBJ-003` documented as completed and shift active engineering follow-through to `OBJ-005` action-pattern standardization / CI enablement
2. Keep DB-backed verification sequential (`composer test:db:reset` -> `composer test:action` -> `composer test`) because suites share `doitly_test`
3. Backfill only high-value remaining branches discovered during feature work (avoid exhaustive low-value helper exit-path tests)
