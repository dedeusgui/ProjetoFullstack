# Development Docs Hub

This `docs/` folder is the operational documentation workspace for developers and AI agents working on this project.

Use this folder to understand:
- current priorities and blockers
- future objectives and how daily work contributes to them
- architectural decisions (ADRs)
- feature-level specs and progress
- runbooks for repeatable development tasks

## Start Here

1. `docs/STATUS.md` (current state, blockers, next step)
2. `docs/FUTURE_OBJECTIVES.md` (strategic objectives and success criteria)
3. `docs/WORKLOG.md` (chronological session history)
4. `docs/ADR/INDEX.md` (decision index)
5. `docs/features/_index.md` (feature workspaces)

## Documentation Rules (Short Version)

- Update `docs/WORKLOG.md` at the end of each session.
- Update `docs/STATUS.md` whenever current state or blockers change.
- Link every meaningful work item to one or more objective IDs from `docs/FUTURE_OBJECTIVES.md`.
- Create an ADR for cross-cutting technical decisions.
- Prefer linking to root `README.md` and `SYSTEM_ARCHITECTURE.md` instead of duplicating long explanations.

## Reading Map

- Project/product overview: `README.md`
- Architecture narrative: `SYSTEM_ARCHITECTURE.md`
- Stable project context: `docs/context/project-overview.md`
- Request lifecycle and runtime behavior: `docs/architecture/request-lifecycle.md`
- Testing strategy and current testing rollout: `docs/architecture/testing-strategy.md`

## Session End Checklist

1. Append a session entry to `docs/WORKLOG.md`
2. Update impacted feature progress in `docs/features/*/progress.md`
3. Update `docs/STATUS.md`
4. Add or update ADR if a major decision was made
5. Note which objective IDs were advanced

