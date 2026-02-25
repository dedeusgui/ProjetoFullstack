# Developer Workflow (Internal)

This file defines the internal development workflow used for implementation and handoffs.

## Core Rules

- Keep changes small and verifiable.
- Prefer adding tests for behavioral changes.
- Record session progress in `docs/WORKLOG.md`.
- Link work to objective IDs from `docs/FUTURE_OBJECTIVES.md`.
- Create ADRs for cross-cutting decisions.

## Session Workflow

1. Read `docs/STATUS.md`
2. Read relevant feature doc(s) and ADRs
3. Implement and verify work
4. Update docs:
   - `docs/WORKLOG.md`
   - `docs/STATUS.md`
   - feature `progress.md`
   - ADR if needed

## Documentation Expectations

- `STATUS.md` = current snapshot only
- `WORKLOG.md` = chronological, append-only history
- `features/*/spec.md` = intent + approach + acceptance criteria
- `features/*/progress.md` = current feature state
- ADRs = important technical decisions and consequences

## Commit Hygiene (Recommended)

- Use conventional commit style when possible
- Keep docs updates in the same commit as the related technical change when they explain or track that change

## Handoff Guidance

At handoff time, include:
- what changed
- what remains
- blockers
- verification performed
- exact next command(s) or next file(s) to inspect

