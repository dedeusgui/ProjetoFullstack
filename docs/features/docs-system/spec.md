# Feature Spec: Development Documentation System

- Related Objectives: `OBJ-004`
- Status: in_progress

## Goal

Create a single-source documentation workspace (`docs/`) that supports development execution, progress tracking, decisions, and handoffs.

## Scope

### In

- Centralize engineering documentation under `docs/`
- Keep only the GitHub-facing `README.md` at the repository root
- Define canonical source locations to reduce duplication
- Add a single engineering handbook for coding quality, review, and verification standards
- Align templates/workflow docs with verification evidence requirements

### Out

- CI/docs automation tooling
- Runtime code refactors
- New static analysis tooling adoption

## Required Capabilities

- Session-based worklog with objective links
- Future objectives catalog with IDs
- Current status snapshot
- ADR index and records
- Feature workspaces
- Runbooks and templates

## Design Principles

- Developers and AI agents should be able to resume work from one place
- Track both tactical progress and strategic objective impact
- Keep stable docs separate from chronological logs
- Prefer links over duplicating long docs
- Keep process strict enough to prevent low-quality changes, but lightweight enough to use daily

## Approach

- Use `docs/README.md` as the canonical navigation hub and source map
- Move root architecture documentation into `docs/architecture/system-architecture.md`
- Add `docs/standards/engineering-handbook.md` as the canonical quality/process reference
- Keep `docs/context/development-conventions.md` focused on repo-specific conventions
- Update templates so verification evidence and risks are captured consistently

## Public Interfaces / Behavior Changes

- Root `README.md` becomes product + quickstart + links (GitHub-facing)
- `docs/README.md` becomes the engineering entrypoint
- New canonical path: `docs/architecture/system-architecture.md`
- New canonical path: `docs/standards/engineering-handbook.md`

## Verification Strategy

- Validate moved/updated file paths and internal links
- Confirm documented Composer commands exist and execute successfully
- Review docs for duplicated or conflicting guidance across hub/conventions/handbook

## Initial Seed Content

- Current testing rollout status and blockers
- Autoload vs bootstrap convention explanation
- Current architecture/testing snapshot
- Engineering handbook (clean code / SOLID / review / verification gates)
