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

## Implemented Decisions

- PHPUnit is the standard test framework
- Action tests use dedicated MySQL schema
- Action scripts are adapted to extracted handlers for testability

## Next Milestones

1. Execute API/stats coverage expansion slice (see `coverage-expansion-plan.md`, Phase 2A)
2. Expand auth flow coverage (service + action handlers)
3. Expand profile/export and remaining habit action coverage

## Dependencies / Constraints

- PHP 8.2+ CLI compatibility
- Local validation baseline currently verified on `PHP 8.5.0` (CLI)
- Composer dev dependencies installed
- MySQL/MariaDB running locally
