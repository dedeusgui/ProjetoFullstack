# AGENTS.md

This file is the operational guide for AI coding agents working in this repository.

Use it to decide:
- what folder/layer to change for a given task
- what checks to run before finishing
- what docs to update
- which canonical docs to read for detailed rules

This file is not the canonical source for architecture or quality policy. Those live under `docs/`.

## Start Here (Required Reading Order)

Before making meaningful changes, read the minimum needed context in this order:

1. `docs/STATUS.md`
2. `docs/README.md`
3. Relevant feature docs in `docs/features/*`
4. `docs/architecture/system-architecture.md`
5. `docs/standards/engineering-handbook.md`
6. Relevant ADRs in `docs/ADR/`

Guideline:
- Read only what is needed for the task.
- Prefer targeted file inspection over scanning the whole repo.

## Repo Map: What To Use For What

### Core folders and responsibilities

- `public/`
  - Server-rendered pages and UI composition
  - Rendering, layout, page-level wiring
  - No heavy business logic or SQL

- `actions/`
  - HTTP adapters / entrypoints (POST/GET, redirects, JSON responses)
  - Request validation, auth/csrf checks, response orchestration
  - Delegate business logic to `app/*`

- `app/`
  - Domain/application logic, services, policies, payload builders, support utilities
  - Reusable logic that should be testable without HTTP globals

- `app/repository/`
  - SQL queries and persistence operations
  - DB-oriented row/aggregate access

- `config/`
  - Bootstrap, helper integration layers, compatibility wrappers, DB connection
  - Transitional code may exist here; do not add new business logic here

- `sql/`
  - Unified schema snapshot and DB objects (`sql/doitly_unified.sql`)

- `tests/`
  - PHPUnit unit/action tests and test support utilities

- `docs/`
  - Canonical engineering docs, runbooks, ADRs, feature specs/progress, templates

### Task routing examples (common cases)

- UI layout/markup bug:
  - Start in `public/*` and related assets
  - If data shaping logic is mixed into the page, move/route it into `app/*`

- Form/action behavior change:
  - Update `actions/*` for HTTP orchestration
  - Put reusable logic in `app/Actions/*` handlers and/or `app/*` services

- Business rule change (habits, stats, achievements, recommendations):
  - Change `app/*` first
  - Update repositories only if query/persistence behavior changes

- New query or persistence behavior:
  - Add/change repository code in `app/repository/*`
  - Call from `app/*` service/query layer

- Schema change:
  - Update `sql/doitly_unified.sql`
  - Run DB reset + impacted tests
  - Update relevant docs/runbooks/feature specs

- Docs/process change:
  - Update `docs/*`
  - Update root `README.md` only if GitHub-facing product/quickstart content changes

## Non-Negotiable Boundary Rules (Repo-Specific)

- Do not add SQL to `public/*`.
- Do not add domain SQL to `actions/*` (except documented temporary exceptions).
- Do not put new business logic in `config/*` or `config/app_helpers.php`.
- Do not import `actions/*` as libraries from `public/*`.
- Do not use `$_GET`, `$_POST`, `$_SESSION`, `header()`, or `exit` inside `app/*`.
- Do not add hidden side effects to `get*` methods.
- Do not duplicate long docs content when a canonical doc already exists.

If a temporary exception is necessary:
- document the exception and removal trigger
- update architecture/debt notes and/or feature docs
- add an ADR if the decision is cross-cutting or durable

## Coding Standards and Quality Defaults (Agent Summary)

Use `docs/standards/engineering-handbook.md` as the canonical policy.

Defaults to follow:
- Internal identifiers/comments/docs in English
- Prefer maintainable and explicit code over clever shortcuts
- Keep changes small, reversible, and cohesive
- Use useful comments only (intent, invariants, tradeoffs, non-obvious constraints)
- Keep side effects explicit in names (`sync*`, `refresh*`, `persist*`)
- Apply SOLID pragmatically to improve clarity/testability, not as ceremony

## Verification Rules (What To Run Before Finishing)

Use the minimum sufficient checks for the change type, but do not under-verify.

### Available repo commands

- `composer qa`
- `composer test`
- `composer test:unit`
- `composer test:action`
- `composer test:db:reset`

### Change-type guidance

- Docs-only changes
  - Validate paths/links and command names
  - Run commands only if docs changed workflow/command claims and validation is needed

- Pure PHP logic changes (non-HTTP)
  - `php -l` on changed files
  - `composer test:unit` (or targeted relevant tests)
  - Consider `composer qa`

- Action/endpoint changes
  - `php -l` on changed files
  - relevant tests
  - `composer test:action`
  - use `composer test` for higher-risk/cross-cutting changes

- DB/persistence/schema changes
  - `composer test:db:reset`
  - impacted tests
  - `composer test` when risk is moderate/high

Always report:
- exact commands run
- key results
- checks intentionally not run (and why)

## Documentation Update Rules (Required For Meaningful Changes)

When your change is meaningful (behavior, architecture, process, or tracking impact), update the relevant docs:

- `docs/WORKLOG.md`
  - Add a session entry with exact verification commands/results

- `docs/STATUS.md`
  - Update if current state, blockers, or next recommended step changed

- `docs/features/*/progress.md`
  - Update impacted feature progress, verification evidence, risks, next actions

- `docs/features/*/spec.md`
  - Update when scope, approach, interfaces, risks, or verification strategy changed

- `docs/ADR/*.md`
  - Add/update when a cross-cutting technical decision or durable exception is introduced

Use:
- `docs/CONTRIBUTING_DEV.md` for workflow expectations
- `docs/standards/engineering-handbook.md` for review/verification policy

## Agent Workflow (End-to-End)

1. Read `docs/STATUS.md` and the minimum relevant docs.
2. Identify the correct layer/folder for the task before editing.
3. Implement the smallest safe change that solves the task.
4. Verify based on change type and risk.
5. Update docs/worklog/progress/spec/ADR as needed.
6. Report what changed, what was verified, and remaining risks/next steps.

## Safe Change Practices (No Garbage Rule)

- Do not add short-term fixes without documenting why and when they should be removed.
- Prefer existing project patterns over inventing a new pattern for one file.
- If touched code is unclear, improve naming/extraction in the touched area when low-risk.
- Avoid speculative abstractions unless there is a real current need.
- Preserve behavior unless the task explicitly changes it.
- If unsure where logic belongs, inspect adjacent files and follow the established pattern.

## Handoff / Response Expectations

When finishing work, include:
- what changed
- why it changed
- verification performed (exact commands + key results)
- blockers / remaining risks
- next recommended step (if applicable)

## Canonical References (Quick Links)

- `docs/README.md`
- `docs/STATUS.md`
- `docs/standards/engineering-handbook.md`
- `docs/architecture/system-architecture.md`
- `docs/context/development-conventions.md`
- `docs/CONTRIBUTING_DEV.md`
- `docs/ADR/INDEX.md`
- `docs/features/_index.md`
