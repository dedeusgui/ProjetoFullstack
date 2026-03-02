# Development Docs Hub

This `docs/` folder is the canonical engineering documentation workspace for developers and AI agents working on this project.

Use this folder to understand:
- current priorities and blockers
- strategic objectives and progress tracking
- architecture boundaries and decisions
- feature-level specs, acceptance criteria, and progress
- runbooks and repeatable workflows
- engineering quality standards and review/verification gates

## Start Here

1. `docs/STATUS.md` (current state, blockers, next step)
2. `docs/FUTURE_OBJECTIVES.md` (strategic objectives and success criteria)
3. `docs/standards/engineering-handbook.md` (quality standards, review gates, definition of done)
4. `docs/WORKLOG.md` (chronological session history)
5. `docs/ADR/INDEX.md` (decision index)
6. `docs/features/_index.md` (feature workspaces)

## Canonical Sources (Avoid Duplication)

- Root `README.md`: GitHub-facing product overview and quickstart only (public-facing, PT-BR)
- `docs/README.md`: engineering docs navigation hub
- `docs/architecture/system-architecture.md`: architecture boundaries and refactor direction
- `docs/standards/engineering-handbook.md`: clean code, SOLID, review, testing/verification policy
- `docs/context/development-conventions.md`: repo-specific implementation conventions

If a topic already has a canonical doc, summarize and link to it instead of duplicating the full content.

## Reading Map

- Product overview and quickstart (GitHub-facing, PT-BR): `README.md`
- Architecture narrative and boundaries: `docs/architecture/system-architecture.md`
- Stable project context: `docs/context/project-overview.md`
- Development workflow summary: `docs/CONTRIBUTING_DEV.md`
- Repo-specific conventions: `docs/context/development-conventions.md`
- Request lifecycle and runtime behavior: `docs/architecture/request-lifecycle.md`
- Testing strategy and rollout context: `docs/architecture/testing-strategy.md`
- Runbooks: `docs/runbooks/`

## Documentation Rules (Short Version)

- Update `docs/WORKLOG.md` at the end of each meaningful session.
- Update `docs/STATUS.md` whenever current state, blockers, or next step changes.
- Link meaningful work to one or more objective IDs from `docs/FUTURE_OBJECTIVES.md`.
- Create an ADR for cross-cutting technical decisions and documented exceptions.
- Follow verification and review gates in `docs/standards/engineering-handbook.md`.

## Session End Checklist

1. Append a session entry to `docs/WORKLOG.md` (include exact verification commands/results)
2. Update impacted feature progress in `docs/features/*/progress.md`
3. Update `docs/STATUS.md` if state/blockers/next step changed
4. Add or update an ADR if a major decision or intentional exception was introduced
5. Note which objective IDs were advanced and the impact (`on-track`, `at-risk`, `blocked`)
