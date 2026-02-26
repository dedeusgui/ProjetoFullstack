# Future Objectives

This file tracks strategic objectives and provides IDs used in `docs/WORKLOG.md`, `docs/STATUS.md`, feature docs, and ADRs.

## Vision (6-12 Months)

- Make Doitly maintainable and testable enough to ship features faster without regressions.
- Standardize engineering workflow (documentation, decisions, tests, and handoffs).
- Expand user-facing features with confidence (habits, analytics, recommendations, gamification).

## Current Objectives

## OBJ-001 - Standardize Local Development Environment

- Status: `done`
- Current note: PHP CLI upgrade completed and verified (`php -v` shows PHP 8.5.0). Required extensions were enabled (`openssl`, `mbstring`, `curl`, `mysqli`, `pdo_mysql`), Composer dev dependencies were installed, and local test commands ran successfully.
- Why it matters: Consistent PHP/Composer/MySQL setup is required for reliable testing and development.
- Success criteria:
  - PHP CLI version is compatible with project target (8.2+)
  - Composer installs dev dependencies successfully
  - `composer test:db:reset` and at least one test suite runs locally
- Target window: Near-term
- Related features/ADRs:
  - `docs/features/testing-rollout/spec.md`
  - `docs/ADR/ADR-0003-phpunit-mysql-test-strategy.md`

## OBJ-002 - Establish Automated Testing Foundation

- Status: `done`
- Current note: Foundation is implemented and locally validated. `composer test:db:reset`, `composer test:unit`, `composer test:action`, and `composer test` are working on the upgraded environment.
- Why it matters: Prevent regressions while refactoring and adding new features.
- Success criteria:
  - PHPUnit config and suites exist
  - Reusable test bootstrap/utilities exist
  - MySQL test schema reset workflow is documented and repeatable
  - First-wave unit and action tests are implemented
- Target window: Near-term
- Related features/ADRs:
  - `docs/features/testing-rollout/spec.md`
  - `docs/ADR/ADR-0002-action-handler-testability-pattern.md`
  - `docs/ADR/ADR-0003-phpunit-mysql-test-strategy.md`

## OBJ-003 - Expand Test Coverage to Critical Flows

- Status: `done`
- Current note: Phase 2A-2F coverage rollout was completed and locally validated across API/stats, auth, habits, profile/export, repositories/support, recommendation/achievement/progress, and stable helper globals.
- Why it matters: Coverage must move beyond the initial habit flow and pure utilities.
- Success criteria:
  - Auth, API, and stats flows have representative tests
  - High-risk failure paths are covered
  - Coverage expansion plan is tracked by milestones
- Target window: Mid-term
- Related features/ADRs:
  - `docs/features/testing-rollout/spec.md`

## OBJ-004 - Build a Single-Source Development Documentation System

- Status: `in_progress`
- Current note: `docs/` is now the canonical engineering documentation workspace (including architecture and a unified engineering handbook). Remaining work is sustained usage and freshness across sessions.
- Why it matters: Reduces context loss and improves handoff quality across sessions.
- Success criteria:
  - `docs/` hub exists with navigation and usage rules
  - session worklog + objective tracking are active
  - ADRs are indexed and linked
  - feature workspaces and templates exist
  - engineering quality standards and verification gates are documented and discoverable
- Target window: Immediate
- Related features/ADRs:
  - `docs/features/docs-system/spec.md`
  - `docs/ADR/ADR-0001-docs-system-and-progress-logging.md`

## Planned Objectives (Next)

## OBJ-005 - Standardize HTTP Action Patterns

- Status: `planned`
- Why it matters: Action scripts currently mix procedural and extracted patterns; consistency improves maintainability.
- Success criteria:
  - Action handler/adaptor pattern documented and applied to more actions
  - Response handling conventions are standardized
  - Security and redirect behaviors remain equivalent

## OBJ-006 - Improve Observability and Error Diagnostics

- Status: `planned`
- Why it matters: Faster debugging and safer releases.
- Success criteria:
  - Error contexts are consistently logged
  - Common failure runbooks exist
  - Production-safe debug workflow is documented

## Deferred Objectives

- CI-based test automation (local test stability prerequisite is now met; implementation timing remains a prioritization decision)
- Broader docs automation/enforcement (linting/checks for docs freshness)
