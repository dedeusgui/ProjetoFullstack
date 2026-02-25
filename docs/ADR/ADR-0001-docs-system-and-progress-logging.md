# ADR-0001: Docs System and Objective-Linked Progress Logging

- Status: accepted
- Date: 2026-02-25

## Context

Development progress and technical context were previously spread across root docs and session memory, which increases handoff friction and causes context loss between sessions.

The project also needs a consistent way to connect day-to-day implementation work to longer-term objectives.

## Decision

Adopt a centralized `docs/` folder as the operational documentation workspace with:

- `STATUS.md` for current snapshot
- `WORKLOG.md` as append-only session history
- `FUTURE_OBJECTIVES.md` for strategic objectives (`OBJ-xxx`)
- `ADR/` for architectural decisions
- `features/` workspaces for specs and progress
- templates and runbooks for repeatable workflows

Require worklog entries to include objective IDs and objective impact.

## Consequences

### Positive

- Faster handoffs for developers and AI agents
- Better traceability from sessions -> features -> decisions -> objectives
- Reduced duplication of ad hoc notes

### Negative / Cost

- Ongoing documentation maintenance overhead
- Risk of stale docs if the process is not followed

## Alternatives Considered

- Keep notes only in `README.md` / `SYSTEM_ARCHITECTURE.md`
  - Rejected: mixes stable project docs with session-level operational tracking
- Use only a single changelog/worklog file
  - Rejected: weak discoverability for decisions and active feature state

## Links

- `docs/README.md`
- `docs/WORKLOG.md`
- `docs/FUTURE_OBJECTIVES.md`
- `docs/features/docs-system/spec.md`

