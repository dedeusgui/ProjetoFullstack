# Developer Workflow (Internal)

This file defines the operational workflow for implementation and handoffs.

For coding quality rules, review checklists, SOLID guidance, clean architecture expectations, and verification gates, use:
- `docs/standards/engineering-handbook.md`

## Core Rules

- Keep changes small, reversible, and verifiable.
- Prefer tests for behavioral changes.
- Follow the verification matrix in the engineering handbook.
- Record session progress in `docs/WORKLOG.md`.
- Link work to objective IDs from `docs/FUTURE_OBJECTIVES.md`.
- Create ADRs for cross-cutting decisions or intentional boundary exceptions.

## Session Workflow

1. Read `docs/STATUS.md`
2. Read relevant feature docs, ADRs, and handbook sections
3. Implement work using the architecture boundaries in `docs/architecture/system-architecture.md`
4. Run verification required by the change type
5. Update docs:
   - `docs/WORKLOG.md`
   - `docs/STATUS.md` (if current state/blockers/next step changed)
   - impacted feature `progress.md`
   - ADR (if needed)

## Documentation Expectations

- `docs/README.md` = navigation hub and canonical source map
- `docs/STATUS.md` = current snapshot only
- `docs/WORKLOG.md` = chronological, append-only history with verification evidence
- `docs/features/*/spec.md` = intent + approach + acceptance criteria + verification strategy
- `docs/features/*/progress.md` = current feature state + open risks + next actions
- ADRs = important technical decisions, tradeoffs, and consequences

## Commit Hygiene (Recommended)

- Use conventional commit style when possible.
- Keep docs updates in the same commit as the related technical change when they explain or track that change.
- If docs-only changes alter canonical paths, update links in the same commit.

## Handoff Guidance

At handoff time, include:
- what changed
- what remains
- blockers / risks
- verification performed (exact commands + key results)
- exact next command(s) or next file(s) to inspect
