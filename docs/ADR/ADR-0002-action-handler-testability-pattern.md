# ADR-0002: Action Handler Extraction Pattern for Testability

- Status: accepted
- Date: 2026-02-25

## Context

HTTP action scripts in `actions/*.php` directly perform bootstrap, request validation, DB calls, redirects, and `exit` behavior. This makes action-level tests difficult and brittle.

The project needed first-wave action tests for habit create/update/toggle flows.

## Decision

Adopt a handler/adapter pattern for actions:

- Keep `actions/*.php` as thin HTTP adapters/entrypoints
- Move behavior into `app/Actions/*` handler classes
- Standardize handler outputs through `App\\Actions\\ActionResponse`
- Apply handler outputs centrally via `actionApplyResponse()`

## Consequences

### Positive

- Action behavior becomes testable without invoking `header()` / `exit`
- Preserves route/file compatibility
- Supports gradual migration (not all actions need refactor at once)

### Negative / Cost

- Two-layer action structure adds some indirection
- Mixed old/new patterns will exist temporarily

## Alternatives Considered

- Test action scripts directly with output buffering and heavy globals mocking
  - Rejected: brittle and hard to maintain
- Full framework migration
  - Rejected for this phase: too large for immediate testing goals

## Links

- `docs/features/testing-rollout/spec.md`
- `app/Actions/ActionResponse.php`
- `config/action_helpers.php`
