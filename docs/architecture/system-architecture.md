# System Architecture (Current State + Refactor Direction)

This document describes the **current architecture**, the **standards adopted in the refactor**, and the **next steps** to keep the codebase organized, maintainable, and ready for larger features (including a future recommendation-system upgrade and API-first evolution).

This is the canonical architecture boundary document for the project.

For project-wide engineering quality policy (clean code, SOLID guidance, review checklist, verification gates, and definition of done), use `docs/standards/engineering-handbook.md`. Keep this file focused on architecture boundaries, ownership, and refactor direction.

## Goals

- Keep the current monolith stable and easy to maintain
- Reduce coupling between UI, HTTP, business logic, and data access
- Standardize internal naming in English
- Prepare clear boundaries for future API extraction and larger modules
- Improve software quality without forcing a framework rewrite

## Architectural Overview

```text
public/                    -> UI pages (server-rendered PHP)
   -> actions/             -> HTTP entrypoints (web actions + JSON endpoints)
      -> app/              -> Application services / domain logic / payload builders
         -> app/repository -> SQL persistence (MySQLi)
            -> config/     -> Bootstrap, auth helpers, app helper compatibility, DB
               -> sql/     -> Unified schema snapshot (source of truth)
```

## Current Structure (Refactor Baseline)

### `public/`

Responsibilities:
- Render pages and UI fragments
- Read prepared data from internal builders/services
- Submit forms to `actions/*`
- Own page composition and responsive behavior (CSS/layout), without moving business logic into views

Rules:
- Must not import `actions/*` as libraries
- Should avoid SQL and heavy business logic
- Can use helper wrappers during migration, but new logic should go to `app/*`
- Prefer shared internal layout styles (`public/assets/css/dashboard.css`) for dashboard/habits/history responsive fixes before adding page-local inline styles

### `actions/`

Responsibilities:
- Handle HTTP requests (POST/GET)
- Validate auth / CSRF / request method
- Call services/builders in `app/*`
- Return redirects or JSON responses

Rules:
- No business rules beyond request validation and response orchestration
- No direct domain SQL (except transitional legacy code being migrated)

Examples (current standardized names):
- `actions/api_stats_get.php`
- `actions/api_habits_get.php`
- `actions/habit_toggle_completion_action.php`
- `actions/export_user_data_csv_action.php`

### `app/`

Responsibilities:
- Domain/application logic
- Query and command services
- Internal API payload builders
- Recommendation logic
- Support utilities (date/formatting)

Namespaces are standardized under `App\...`.

Current modules include:
- `App\Api\Internal`
- `App\Auth`
- `App\Habits`
- `App\Stats`
- `App\Achievements`
- `App\UserProgress`
- `App\Recommendation`
- `App\Repository`
- `App\Support`

### `app/repository/`

Responsibilities:
- SQL access and persistence operations
- Query encapsulation per aggregate/domain

Current repositories:
- `UserRepository`
- `UserSettingsRepository`
- `HabitRepository`
- `CategoryRepository`

### `config/`

Responsibilities:
- Application bootstrap
- Authentication/session helper layer
- Action helper layer (redirect, CSRF, request validation)
- Transitional app helper compatibility layer
- Database connection

Current files (standardized):
- `config/bootstrap.php`
- `config/auth_helpers.php`
- `config/action_helpers.php`
- `config/app_helpers.php` (transitional compatibility layer; still too large)
- `config/database.php`

### `sql/`

Responsibilities:
- Unified database schema snapshot (`sql/doitly_unified.sql`)
- Procedures, views, triggers, indexes, constraints

Status:
- Still needs full English identifier/comment standardization in a later pass.

## Refactor Progress (Implemented)

### 1. Autoload + Namespaces
- `composer.json` added with `App\\` autoload configuration
- `config/bootstrap.php` loads Composer autoload when available
- Fallback PSR-4-like autoloader exists for environments without Composer on PATH
- `app/*` classes migrated to `App\...` namespaces
- Internal `require_once` chains in `app/*` removed

### 2. HTTP / App Layer Decoupling
- `public/dashboard.php` and `public/history.php` no longer depend on `actions/*` as libraries
- Internal payload builders created:
  - `App\Api\Internal\StatsApiPayloadBuilder`
  - `App\Api\Internal\HabitsApiPayloadBuilder`
- `actions/api_*.php` now act as thin HTTP wrappers

### 3. Habit Domain Cleanup
- Habit mutations consolidated into `HabitCommandService`
- Habit SQL centralized in repositories (`HabitRepository`, `CategoryRepository`)
- Habit schedule/date logic extracted into:
  - `HabitSchedulePolicy`
  - `DateFormatter`
  - `TimeOfDayMapper`

### 4. Naming Standardization (Internal)
- Config file names standardized (`database.php`, `auth_helpers.php`, `app_helpers.php`)
- Auth helper functions renamed to English:
  - `isUserLoggedIn()`
  - `getAuthenticatedUserId()`
  - `getAuthenticatedUserRecord()`
  - `signInUser()`
  - `signOutUser()`
  - `requireAuthenticatedUser()`
  - `getUserInitials()`
- Key action routes renamed to clearer English names

### 5. Service Boundaries Added for Future Refactor
Services introduced to reduce coupling and centralize domain logic:
- `App\Stats\StatsQueryService`
- `App\Achievements\AchievementService`
- `App\UserProgress\UserProgressService`
- `App\Habits\HabitQueryService`

Progress in this area:
- `AchievementService` now contains real achievement sync logic (including icon mapping and perfect-day streak metrics)
- `UserProgressService` now contains real XP/level calculation + persistence logic
- `config/app_helpers.php` delegates achievements/progress functions to services via compatibility wrappers
- Core pages/actions now call explicit methods (`syncUserAchievements`, `refreshUserProgressSummary`)
- Dashboard payload now includes `best_streak` and `total_completions`, removing duplicate page queries
- Stats/Habits read paths now have explicit query repositories:
  - `App\Repository\StatsRepository`
  - `App\Repository\HabitQueryRepository`
- `StatsQueryService` and `HabitQueryService` now orchestrate repository-backed reads (instead of being only helper facades)
- `public/habits.php` now consumes a page-ready payload from `App\Api\Internal\HabitsApiPayloadBuilder`
- Shared user-local date/timezone resolution is centralized in `App\Support\UserLocalDateResolver` and reused by Stats/Habits/Achievements

## Coding Standards (Project Rules)

## Language Standard
- Internal identifiers (classes, methods, variables, comments, docs): **English**
- User-facing UI text: **PT-BR by default**, but intentional mixed usage with common product/UX English terms (for example `Dashboard`) is allowed
- `README.md`: **Portuguese**

## Layering Rules
- `public/*`:
  - no SQL
  - no heavy business logic
  - no imports from `actions/*`
- `actions/*`:
  - HTTP orchestration only
  - no domain SQL
  - delegate to `app/*`
- `app/*`:
  - no `$_GET`, `$_POST`, `$_SESSION`, `header()`, `exit`
  - no direct routing concerns
- `Repository`:
  - SQL and persistence only
  - no HTTP concerns

## Stats + Habits Ownership Matrix (Current Target)

- `public/dashboard.php`, `public/history.php`, `public/habits.php`
  - Render UI only
  - Consume prepared payloads
  - No query orchestration or row-shaping loops for domain data

- `App\Api\Internal\StatsApiPayloadBuilder`, `App\Api\Internal\HabitsApiPayloadBuilder`
  - Build page/API payloads and view-model arrays
  - Map internal rows to UI/API-friendly shapes
  - Orchestrate app services (no direct SQL)

- `App\Stats\StatsQueryService`, `App\Habits\HabitQueryService`
  - Domain/application read orchestration
  - Compose repositories + policies (timezone, schedule, ranges)
  - No HTTP concerns

- `App\Repository\StatsRepository`, `App\Repository\HabitQueryRepository`
  - SQL queries for stats/history/habit reads
  - Return DB-oriented rows/aggregates only

- `config/app_helpers.php`
  - Transitional compatibility wrappers and utilities only
  - No Stats/Habits query functions should remain here

## Boundary Enforcement Rules (Stats + Habits)

- `public/*` pages should prefer internal payload builders over direct helper query calls
- `public/*` must not assemble domain payloads from raw SQL rows
- `actions/*` must not add new domain SQL; route through `app/*`
- `config/app_helpers.php` may only keep utilities and narrow compatibility wrappers (achievements/progress during migration)
- Any intentional temporary violation must be listed in the manual exceptions table below

## Duplicate Cleanup Policy

- Remove dead/unreachable code immediately after a successful migration
- If the same logic is implemented in 2+ domain services, centralize it in `App\Support\*` (or a repository when SQL-related)
- Prefer payload-builder private mappers/helpers for repeated row-to-payload transformations
- Track deferred duplicate cleanup items explicitly in architecture debt or exceptions notes

## Naming Rules
- Classes: `PascalCase`
- Methods/functions: `camelCase`
- Properties/variables: `camelCase`
- Payload keys (JSON/arrays): `snake_case` (when externally consumed)
- SQL identifiers: `snake_case`

## Transitional Helper Policy (`config/app_helpers.php`)

`config/app_helpers.php` is currently a **compatibility layer** with utilities and limited wrappers.

Rules from now on:
- Do not add new business logic here
- Do not add new SQL here
- Prefer `app/*` services/repositories for new code
- Keep only utilities and explicitly temporary wrappers

## Known Architectural Debt (Current)

These are the highest-priority remaining issues:

1. `config/app_helpers.php` is still a transitional file and should be reduced further over time:
- pure utilities (acceptable)
- achievement/progress compatibility wrappers (temporary)
- remaining helper naming/API compatibility debt

2. Some `get*` functions still produce side effects
- Example: achievements/progress sync patterns hidden behind reads

3. SQL schema snapshot (`sql/doitly_unified.sql`) still needs full English standardization
- identifiers/comments/procedure naming consistency pass is pending

4. User-facing routes are only partially normalized
- core action names were improved, but a full naming audit is still pending if desired

## Manual Boundary Exceptions (Strict Tracking)

| ID | File | Violation Type | Why It Exists | Target Layer | Removal Trigger | Status |
|---|---|---|---|---|---|---|
| BE-001 | `config/app_helpers.php` | Legacy SQL/helper implementations still present below compatibility wrappers | Removed in helper cleanup pass; keep entry as historical checkpoint until next audit | `App\Stats\*`, `App\Habits\*`, repositories | Verify no deleted helper call sites remain, then remove this exception entry | `removed` |
| BE-002 | `actions/export_user_data_csv_action.php` | Direct SQL inside action | Export flow has not been moved to a dedicated export/query service yet | `App\Export\*` or repository-backed service | Refactor export action to service/repository in Export/Profile slice (or opportunistic follow-up) | `open` |
| BE-003 | `App\Api\Internal\StatsApiPayloadBuilder` | Recommendation snapshot SQL inside payload builder | Recommendation snapshot repository/service not extracted yet | `App\Repository\Recommendation*` / recommendation service | Extract recommendation snapshot persistence/query into repository | `open` |

## Duplicate Cleanup Backlog (Deferred)

- `app\recommendation\BehaviorAnalyzer.php`: private metrics methods overlap with stats metrics behavior (evaluate consolidation behind `StatsQueryService` or a dedicated metrics service)
- `app\achievements\AchievementService.php`: private metrics queries (`getTotalHabits`, `getTotalCompletions`, `getBestStreak`) still overlap with stats/repository capabilities (defer to a broader cross-domain metrics cleanup)

## Next Refactor Roadmap (Recommended Sequence)

## Phase A — Remove Heavy Logic from `config/app_helpers.php`

Move functionality to explicit services/repositories:

- `App\Stats\StatsRepository` + `StatsQueryService`
  - completion summaries
  - history
  - trend metrics
  - monthly/category stats

- `App\Achievements\AchievementRepository` + `AchievementService`
  - move remaining persistence reads into repositories (service logic already extracted)

- `App\UserProgress\UserProgressService`
  - keep explicit refresh/read flows and remove remaining legacy helper entry points

Current status:
- achievements/progress wrappers already migrated to services

Result target:
- `config/app_helpers.php` becomes a thin compatibility wrapper (or can be removed later)

## Phase B — Make Side Effects Explicit

Rule to enforce:
- `get*` methods must be read-only

Refactor examples:
- `getUserAchievements()` -> split into:
  - `syncUserAchievements(...)`
  - `getUserAchievements(...)`
- `getUserProgressSummary()` -> compute/read path separated from persistence path

Current status:
- explicit methods are already available and used in core pages/actions (`syncUserAchievements`, `refreshUserProgressSummary`)

Result:
- safer behavior
- easier testing
- easier API extraction

## Phase C — SQL Snapshot Standardization (English)

Update `sql/doitly_unified.sql`:
- comments/doc blocks in English
- naming consistency for procedures/views/indexes/constraints
- review identifier naming consistency (keep behavior same)

Because the current plan allows DB reset, this can be a clean snapshot rewrite.

## Phase D — Recommendation System Upgrade Preparation

Before changing the algorithm, stabilize interfaces:
- behavior metrics contract
- trend result contract
- score result contract
- recommendation payload contract
- snapshot persistence behind a repository/service

Result:
- future recommendation improvements become isolated and lower-risk

## Phase E — Future API-First Evolution (Longer Term)

With the current refactor direction, these boundaries are already being prepared:
- `actions/*` as HTTP adapters
- `app/*` as reusable domain/application layer
- internal payload builders as a stepping stone to serializers

Future extraction path:
- reuse `app/*` services behind a dedicated API layer
- gradually separate public web pages from data endpoints

## Quality & Validation Checklist (for future refactors)

When touching architecture-sensitive code, validate:

- `php -l` on all changed PHP files
- No `public/*` -> `actions/*` imports
- No new SQL added to `config/*`
- New logic placed in `app/*` services/repositories
- English naming for internal identifiers/comments
- Route/file references updated after hard renames

## Summary

The codebase is now moving toward a cleaner structure with:
- English-standardized internals
- Composer/autoload + namespaces
- thinner actions
- reusable internal payload builders
- early service boundaries for stats/achievements/progress

The next big win is to fully dismantle the legacy `config/app_helpers.php` into proper modules and make side effects explicit.
