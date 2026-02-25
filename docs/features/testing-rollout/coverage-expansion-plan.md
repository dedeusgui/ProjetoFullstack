# Testing Rollout Coverage Expansion Plan

- Related Objectives: `OBJ-003`, `OBJ-004`
- Status: planned
- Baseline date: 2026-02-25

## Purpose

Document the next steps to build the missing automated tests across the codebase in a pragmatic order (highest risk and easiest leverage first).

## Baseline (Verified)

- Existing suites pass locally:
  - `composer test:unit`
  - `composer test:action`
  - `composer test`
- Current full result: `32 tests`, `90 assertions`
- Current test files: `7`
- Current inventory (PHP only, excluding `public/includes/*` and assets):
  - `app/`: `40` files
  - `actions/`: `13` files
  - `config/`: `7` files
  - `public/` pages: `6` files

## What "Missing Tests" Means (Coverage Policy)

This plan is behavior-first, not "one test per file" by default.

- Add direct tests for business logic, data mapping, and action behavior.
- Prefer testing extracted handlers/services over procedural page scripts when both cover the same behavior.
- Treat view templates (`public/includes/*`) as low priority unless they contain non-trivial logic.
- Frontend JavaScript/UI interactions in `public/*.php` inline scripts are a separate testing track (browser/E2E), not a PHPUnit blocker.

## Current Coverage (Implemented)

- Unit:
  - `HabitSchedulePolicy`
  - `HabitInputSanitizer`
  - `TimeOfDayMapper`
  - `ScoreEngine`
  - `TrendAnalyzer`
  - `HabitRefererRedirectResolver`
- Action:
  - Habit create/update/toggle handlers

## Next Steps (Priority Order)

## Phase 2A: API endpoints and payload building (highest leverage)

Goal: cover the next most user-visible JSON outputs and reduce regression risk in dashboard/history/habits data responses.

### Target files

- `actions/api_stats_get.php`
- `actions/api_habits_get.php`
- `app/Api/Internal/StatsApiPayloadBuilder.php`
- `app/Api/Internal/HabitsApiPayloadBuilder.php`
- `app/Stats/StatsQueryService.php`
- `app/Habits/HabitQueryService.php`

### Test work

- Add action/integration tests for API endpoint behavior:
  - unauthorized request returns `401` JSON error
  - valid request returns `success=true`
  - invalid `view`/`scope` falls back to defaults
  - expected top-level keys exist (`view`, `scope`, `data`, `generated_at`)
- Add focused unit/integration tests for payload builders (or extract smaller collaborators if needed):
  - `dashboard` vs `history` payload shape in stats API
  - habits API `all`, `today`, and `page` scopes
  - payload defaults for nullable fields (category/time/goal values)
  - `today_rate` and counts calculation
  - recommendation snapshot path (`cached` vs `fresh`) in stats payload (at least one representative scenario)
- Add service-level tests for `StatsQueryService` high-risk branches:
  - insufficient data trend result
  - up/down/stable trend deltas
  - completion window summary with no creation date / zero scheduled habits
  - current streak edge cases (gap > 1 day, today/yesterday chain)
  - `getRecentHistory()` day clamp by user creation date

### Suggested files to add

- `tests/Action/Api/StatsApiActionTest.php`
- `tests/Action/Api/HabitsApiActionTest.php`
- `tests/Action/Api/StatsApiPayloadBuilderTest.php` (or `tests/Unit/...` if refactored for DI)
- `tests/Action/Api/HabitsApiPayloadBuilderTest.php`
- `tests/Action/Stats/StatsQueryServiceTest.php`
- `tests/Action/Habits/HabitQueryServiceTest.php`

### Refactor note

If direct testing of `actions/api_*` scripts is too brittle due bootstrap/output side effects, extract handler classes (same pattern used for habit actions) and test handlers first.

## Phase 2B: Auth flow coverage (login/register/logout)

Goal: protect account access flows and validation branches.

### Target files

- `actions/login_action.php`
- `actions/register_action.php`
- `actions/logout_action.php`
- `app/Auth/AuthService.php`

### Test work

- `AuthService`:
  - authenticates valid credentials
  - rejects wrong password / unknown email
  - normalizes email for lookup/register
  - `emailExists()` normalization behavior
  - `register()` success path and repository failure path
  - `updateLastLogin()` delegates without error
- Action behavior:
  - missing fields -> error flash redirect
  - rate limited login -> error flash redirect
  - invalid credentials increments auth failure state
  - successful login signs in and redirects
  - register validation branches (email format, password length, mismatch, duplicate email)
  - logout clears session/auth state and redirects/ends session flow

### Suggested files to add

- `tests/Action/Auth/AuthServiceTest.php`
- `tests/Action/Auth/LoginActionHandlerTest.php` (after extraction)
- `tests/Action/Auth/RegisterActionHandlerTest.php` (after extraction)
- `tests/Action/Auth/LogoutActionHandlerTest.php` (after extraction or helper-level tests)

### Refactor note

Auth actions are still procedural. Extract handler classes before broad branch testing to avoid `header()/exit` coupling and make flash assertions consistent with current action-handler test style.

## Phase 2C: Habit command/query services beyond first-wave handlers

Goal: cover command outcomes used by remaining habit actions and API/page payloads.

### Target files

- `app/Habits/HabitCommandService.php`
- `app/Habits/HabitCompletionService.php`
- `app/Habits/HabitAccessService.php`
- `actions/habit_delete_action.php`
- `actions/habit_archive_action.php`

### Test work

- `HabitCommandService`:
  - create/update with invalid category/habit ownership -> failure messages
  - delete/archive/restore success + failure paths
  - normalization/preparation defaults in `prepareHabitData()`
- `HabitCompletionService`:
  - toggle create completion
  - toggle uncomplete existing completion
  - unscheduled day / unauthorized habit edge paths
  - fallback paths when procedures are unavailable
  - recommendation snapshot invalidation behavior (representative check)
- `HabitAccessService`:
  - ownership true/false delegate behavior
- Actions:
  - delete action supports `habit_id` and `id`
  - archive action `operation=archive|restore` dispatches correctly

### Suggested files to add

- `tests/Action/Habits/HabitCommandServiceTest.php`
- `tests/Action/Habits/HabitCompletionServiceTest.php`
- `tests/Action/Habits/HabitAccessServiceTest.php`
- `tests/Action/Habits/HabitDeleteActionHandlerTest.php` (after extraction)
- `tests/Action/Habits/HabitArchiveActionHandlerTest.php` (after extraction)

## Phase 2D: Profile/settings and export flows

Goal: cover complex validation and transaction behavior with user-facing impact.

### Target files

- `app/Profile/ProfileService.php`
- `actions/update_profile_action.php`
- `actions/reset_appearance_action.php`
- `actions/export_user_data_csv_action.php`
- `app/Repository/UserSettingsRepository.php`

### Test work

- `ProfileService`:
  - email/avatar/color/text-scale validation branches
  - password change validation (missing fields, mismatch, bad current password)
  - success path without password update
  - success path with password update
  - transaction rollback path when repository update fails
  - reset appearance success/failure
- Profile actions:
  - allowed return path resolution/redirect target
  - success updates `$_SESSION['user_email']`
  - success vs error flash keys
- Export CSV action (after extraction strongly recommended):
  - unauthorized/user missing path
  - CSV headers and filename format
  - empty habits / empty achievements sections
  - row counts and summary totals for representative fixture

### Suggested files to add

- `tests/Action/Profile/ProfileServiceTest.php`
- `tests/Action/Profile/UpdateProfileActionHandlerTest.php` (after extraction)
- `tests/Action/Profile/ResetAppearanceActionHandlerTest.php` (after extraction)
- `tests/Action/Profile/ExportUserDataCsvActionHandlerTest.php` (after extraction)

## Phase 2E: Repository and support-layer contracts

Goal: stabilize DB access contracts used by multiple services.

### Target files

- `app/Repository/*.php` (all repositories)
- `app/Support/UserLocalDateResolver.php`
- `app/Support/DateFormatter.php`
- `app/Support/RequestContext.php`
- `app/Actions/ActionResponse.php`
- `app/Recommendation/BehaviorAnalyzer.php`
- `app/Recommendation/RecommendationEngine.php`
- `app/Achievements/AchievementService.php`
- `app/UserProgress/UserProgressService.php`

### Test work

- Repository integration tests with fixture-driven assertions for:
  - counts/finders on happy path
  - null returns for missing rows
  - ownership checks / filtered queries
- Support/value-object tests:
  - `ActionResponse` constructors/getters/response type flags
  - `UserLocalDateResolver` timezone fallback behavior
  - `DateFormatter` formatting edge inputs
  - `RequestContext` request metadata extraction defaults
- Recommendation/achievement/user-progress service tests for representative outputs and edge cases (avoid re-testing already-covered `ScoreEngine`/`TrendAnalyzer` internals).

### Suggested files to add (examples)

- `tests/Action/Repository/StatsRepositoryTest.php`
- `tests/Action/Repository/HabitRepositoryTest.php`
- `tests/Action/Repository/HabitQueryRepositoryTest.php`
- `tests/Action/Repository/UserRepositoryTest.php`
- `tests/Action/Repository/CategoryRepositoryTest.php`
- `tests/Unit/Actions/ActionResponseTest.php`
- `tests/Action/Support/UserLocalDateResolverTest.php`
- `tests/Unit/Support/DateFormatterTest.php`
- `tests/Unit/Support/RequestContextTest.php`
- `tests/Action/Recommendation/BehaviorAnalyzerTest.php`
- `tests/Unit/Recommendation/RecommendationEngineTest.php`
- `tests/Action/Achievements/AchievementServiceTest.php`
- `tests/Action/UserProgress/UserProgressServiceTest.php`

## Phase 2F: Helpers and legacy procedural code cleanup/testing

Goal: reduce regression risk in shared helpers and legacy entry points while avoiding low-value test duplication.

### Target files

- `config/action_helpers.php`
- `config/auth_helpers.php`
- `config/error_helpers.php`
- `config/security_helpers.php`
- `config/bootstrap.php`
- `config/app_helpers.php` (only thin wrappers not already covered via classes)

### Test work

- Unit tests for pure helpers and normalization logic
- Integration tests for session/auth helper behavior where deterministic
- Smoke tests for bootstrap behavior only if isolated execution is practical
- Prefer replacing legacy helper usage with class-based services where testability is poor

### Refactor note

Many helper functions are globally coupled to headers/session/runtime state. Add tests where stable, but do not block progress on exhaustive coverage if extraction to classes provides a cleaner path.

## Execution Sequence (Recommended Sprint Order)

1. `Phase 2A` API coverage (API actions + payload builders + stats query service)
2. `Phase 2B` Auth service + auth action handler extraction/tests
3. `Phase 2C` Habit delete/archive + command/completion service tests
4. `Phase 2D` Profile services/actions + CSV export extraction
5. `Phase 2E` Repository/support/recommendation/achievement expansion
6. `Phase 2F` Helper cleanup/tests and remaining legacy gaps

## Per-Slice Definition of Done

- Tests added for the selected slice (happy path + at least key failure/edge branches)
- `composer test:unit` and/or `composer test:action` pass
- `composer test` passes before merging the slice
- Docs updated:
  - `docs/features/testing-rollout/progress.md`
  - `docs/WORKLOG.md`
  - `docs/STATUS.md` (if milestone status changes)

## Risks / Constraints

- Procedural actions with `header()` and `exit` are hard to test directly without extraction
- DB fixtures may need expansion for stats/history/recommendation scenarios
- Some services instantiate repositories internally (limited DI), increasing integration-test reliance
- Export/CSV and frontend UI behaviors may require specialized test harnesses beyond current PHPUnit setup

## First Concrete Implementation Slice (Recommended Next Session)

Start with `Phase 2A` and complete this minimum set:

1. Add tests for `resolveStatsApiView()` and `resolveHabitsApiScope()` (or extract normalizers into testable helpers/classes).
2. Add API action tests for unauthorized + success JSON response shape for:
   - `actions/api_stats_get.php`
   - `actions/api_habits_get.php`
3. Add `StatsQueryService` tests for trend/status branches (`insufficient`, `up`, `down`, `stable`).
4. Run `composer test`.
5. Update rollout progress/worklog.
