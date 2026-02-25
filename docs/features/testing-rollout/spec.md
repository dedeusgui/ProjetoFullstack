# Feature Spec: Testing Rollout

- Related Objectives: `OBJ-001`, `OBJ-002`, `OBJ-003`
- Status: in_progress

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

## Implemented Decisions

- PHPUnit is the standard test framework
- Action tests use dedicated MySQL schema
- Action scripts are adapted to extracted handlers for testability

## Next Milestones

1. Expand remaining habit command/query/action coverage (Phase 2C)
2. Expand profile/settings/export flow coverage (Phase 2D)
3. Expand repository/support/recommendation/achievement coverage (Phase 2E) and helper cleanup/testing (Phase 2F)

## Dependencies / Constraints

- PHP 8.2+ CLI compatibility
- Local validation baseline currently verified on `PHP 8.5.0` (CLI)
- Composer dev dependencies installed
- MySQL/MariaDB running locally
