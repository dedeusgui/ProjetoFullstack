# System Architecture (Current State + Refactor Direction)

This document describes the **current architecture**, the **standards adopted in the refactor**, and the **next steps** to keep the codebase organized, maintainable, and ready for larger features (including a future recommendation-system upgrade and API-first evolution).

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

Rules:
- Must not import `actions/*` as libraries
- Should avoid SQL and heavy business logic
- Can use helper wrappers during migration, but new logic should go to `app/*`

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

## Coding Standards (Project Rules)

## Language Standard
- Internal identifiers (classes, methods, variables, comments, docs): **English**
- User-facing UI text: **Portuguese for now**
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

## Naming Rules
- Classes: `PascalCase`
- Methods/functions: `camelCase`
- Properties/variables: `camelCase`
- Payload keys (JSON/arrays): `snake_case` (when externally consumed)
- SQL identifiers: `snake_case`

## Transitional Helper Policy (`config/app_helpers.php`)

`config/app_helpers.php` is currently a **compatibility layer** and still contains legacy logic.

Rules from now on:
- Do not add new business logic here
- Do not add new SQL here
- Prefer `app/*` services/repositories for new code
- Only keep wrappers temporarily while migrating call sites

## Known Architectural Debt (Current)

These are the highest-priority remaining issues:

1. `config/app_helpers.php` is still too large (reduced, but still mixed) and contains:
- pure utilities
- SQL queries
- statistics calculations
- legacy habit/query functions still awaiting extraction

2. Some `get*` functions still produce side effects
- Example: achievements/progress sync patterns hidden behind reads

3. SQL schema snapshot (`sql/doitly_unified.sql`) still needs full English standardization
- identifiers/comments/procedure naming consistency pass is pending

4. User-facing routes are only partially normalized
- core action names were improved, but a full naming audit is still pending if desired

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
