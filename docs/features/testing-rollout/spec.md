# Feature Spec: Testing Rollout

- Related Objectives: `OBJ-001`, `OBJ-002`, `OBJ-003`
- Status: phase_2_complete

## Goal

Establish a reliable automated testing foundation (unit + action) and expand coverage incrementally to critical user flows.

## Scope (Current Phase)

- PHPUnit setup
- Test bootstrap and support utilities
- MySQL test schema reset/import
- First-wave unit tests (pure logic)
- First-wave habit action handler tests
- Phase 2A API/stats handler + payload/service coverage
- Phase 2B auth service + action handler coverage
- Phase 2C habits command/completion/access service + delete/archive action coverage
- Phase 2D profile/settings/export service + action handler coverage
- Phase 2E repository/support/recommendation/achievement/user-progress coverage
- Phase 2F helper/legacy helper coverage (stable global helper functions + representative wrapper integration tests)

## Implemented Decisions

- PHPUnit is the standard test framework
- Action tests use dedicated MySQL schema
- Action scripts are adapted to extracted handlers for testability

## Next Milestones

1. Backfill remaining high-value branches discovered in prior slices (e.g., fallback/error paths, hard-to-trigger runtime branches)
2. Continue action-pattern standardization (`OBJ-005`) where helper/global coupling remains
3. Introduce CI automation for the validated local test workflow

## Dependencies / Constraints

- PHP 8.2+ CLI compatibility
- Local validation baseline currently verified on `PHP 8.5.0` (CLI)
- Composer dev dependencies installed
- MySQL/MariaDB running locally
