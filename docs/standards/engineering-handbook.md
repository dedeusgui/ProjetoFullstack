# Engineering Handbook

This document defines the project-wide engineering quality policy for code and documentation changes.

Use this handbook to keep changes future-proof, maintainable, and verifiable. The goal is to avoid short-term fixes that create long-term garbage in the codebase.

## Purpose and Scope

This handbook is the canonical source for:
- clean code expectations
- SOLID guidance (practical, repo-specific interpretation)
- review discipline
- verification and testing gates
- definition of done
- documented exceptions process

This handbook does not replace architecture docs. For layer boundaries and ownership rules, also read:
- `docs/architecture/system-architecture.md`
- `docs/context/development-conventions.md`

## Engineering Principles (Project Defaults)

- Prefer maintainability over cleverness.
- Make behavior explicit; avoid hidden side effects.
- Keep changes small, reviewable, and reversible.
- Add code where it belongs (respect layers) instead of taking shortcuts.
- Do not introduce temporary hacks without documenting the removal trigger.
- Improve touched areas when practical, but do not expand scope without a clear reason.

## Clean Architecture Rules (How We Apply Them Here)

The project uses layered boundaries. The main rule is: each layer owns its concerns and should not absorb another layer's job.

- `public/*`
  - render UI/pages only
  - no SQL
  - no heavy business logic
  - do not import `actions/*` as libraries
- `actions/*`
  - HTTP request/response orchestration only
  - validation/auth/csrf checks and delegation
  - no domain SQL except documented temporary exceptions
- `app/*`
  - domain/application logic
  - no direct HTTP globals (`$_GET`, `$_POST`, `$_SESSION`) and no `header()`/`exit`
- repositories
  - persistence and SQL only
  - no HTTP concerns
- `config/*`
  - bootstrap/integration helpers and transitional compatibility only
  - no new business logic or domain SQL

If a change must violate a boundary temporarily:
- document it in the relevant architecture/debt/exception section
- state the removal trigger
- add an ADR if the exception is cross-cutting or likely to persist

## SOLID Principles (Pragmatic Usage)

Use SOLID as a design pressure, not as ceremony.

### Single Responsibility Principle (SRP)

- Keep handlers focused on one use case.
- Split mixed files that do HTTP + business rules + SQL.
- Prefer extracting policy or mapper classes when logic becomes hard to test.

Signals of SRP problems:
- a function reads request data, performs business logic, runs SQL, and formats output
- a class changes for unrelated reasons (UI shape + persistence + validation)

### Open/Closed Principle (OCP)

- Add behavior by composing services/policies where possible instead of editing many call sites.
- Prefer introducing a small interface/abstraction only when there are multiple real implementations or near-term extension needs.

Avoid premature abstraction with no active use case.

### Liskov Substitution Principle (LSP)

- Keep subclass/implementation behavior compatible with caller expectations.
- Do not change return shapes or side effects unexpectedly when replacing components.

In this repo, this mainly applies to service/repository replacements and helper wrappers.

### Interface Segregation Principle (ISP)

- Keep interfaces/helpers small and task-specific.
- Avoid "god helpers" that expose unrelated operations.
- Prefer feature-specific methods over broad utility classes with mixed concerns.

### Dependency Inversion Principle (DIP)

- Higher-level application logic should depend on abstractions/seams where it improves testability and substitution.
- Introduce seams pragmatically (for handlers/services under test), not everywhere by default.

## Code Quality Standards

## Naming and Readability

- Internal identifiers, comments, and docs: English
- User-facing UI copy: PT-BR by default, but keep intentional mixed terms already used in the product vocabulary (e.g., `Dashboard`) unless the task explicitly requests copy normalization
- Class names: `PascalCase`
- Methods/functions/variables: `camelCase`
- External payload keys (arrays/JSON): `snake_case` when externally consumed
- SQL identifiers: `snake_case`

Choose names that describe domain intent, not implementation trivia.

## Functions and Classes

- Keep functions cohesive and focused on one reason to change.
- Extract repeated logic instead of duplicating across services/actions.
- Prefer pure functions/policies for business rules that are hard to test inline.
- Make side effects explicit in method names (e.g., `sync*`, `refresh*`, `persist*`).

## Side Effects and State

- `get*` methods should be read-only.
- Hidden writes, implicit DB changes, and session mutations increase regression risk.
- If a read path currently has side effects for legacy reasons, document it and plan separation.

## Error Handling

- Fail early on invalid input at boundaries (`actions/*`, validation layers).
- Return consistent error shapes/messages at adapters.
- Avoid swallowing exceptions/errors without adding context.

## Duplication Policy

- Remove dead/unreachable code after safe migration.
- Consolidate duplicated domain logic into `app/*` services/policies/support classes.
- Keep mapping/formatting duplication local only when extraction would reduce clarity.

## Comment Policy (Useful Comments Only)

Comments are required when they explain intent, tradeoffs, constraints, or non-obvious behavior.

Good comments explain:
- why a rule exists
- why a workaround is required
- which invariant must hold
- what can break if code is changed incorrectly

Avoid comments that only restate the code.

Examples:
- Good: "Compatibility wrapper while remaining call sites migrate to `App\\Stats\\StatsQueryService`."
- Bad: "Call function to get stats."

When adding temporary workarounds, include:
- reason
- scope
- removal trigger

## Testing and Verification Policy

Verification is mandatory. Always run the minimum checks required by the change type before considering work complete.

## Change-Type Verification Matrix

### Docs-only changes

Required:
- review paths/links and command names for accuracy

Recommended:
- run any command whose behavior you changed in docs (for example `composer qa`, `composer test`)

Record:
- what was checked manually and what commands were/weren't run

### Pure PHP logic changes (no HTTP adapter changes)

Required:
- `php -l` on changed PHP files
- `composer test:unit` (or relevant targeted tests)

Recommended:
- `composer qa`
- `composer test` if shared behavior or cross-module risk exists

### Action/HTTP handler changes

Required:
- `php -l` on changed PHP files
- relevant unit/action tests
- `composer test:action` for handler/adapter changes

Recommended:
- `composer test`
- manual smoke check of affected flow if UI/API behavior changed

### DB schema / persistence / query changes

Required:
- `php -l` on changed PHP files (if any)
- `composer test:db:reset`
- relevant tests for impacted repositories/services/actions

Recommended:
- `composer test`
- targeted manual smoke checks against non-production local DB

## Current Standard Commands (Repo)

- `composer qa`
- `composer test`
- `composer test:unit`
- `composer test:action`
- `composer test:db:reset`

Use the smallest sufficient set, but do not under-verify.

## Self-Review Checklist (Before Handoff / Commit)

- Correctness: Does the change do exactly what was intended, including failure paths?
- Boundaries: Did any layer absorb responsibilities it should not own?
- Tests: Did I run the required checks for this change type?
- Naming: Are identifiers clear and consistent with project naming rules?
- Comments: Are comments useful and limited to intent/tradeoffs/non-obvious constraints?
- Duplication: Did I add duplicate logic that should be extracted?
- Risk: What regression risk remains and is it documented?
- Docs: Did I update affected docs/templates/status/progress/worklog as needed?

## Definition of Done (Pragmatic Gates)

Work is not done until all applicable items are complete:

- Implementation is complete for the scoped requirement
- Required verification has been executed
- Results are recorded (worklog/feature progress/handoff)
- Architecture boundaries remain intact, or exceptions are documented
- ADR added/updated if a cross-cutting decision was made
- Docs are updated for changed behavior/workflow/paths
- Next step is clear (especially if work is partial)

## Documentation Update Rules

Update these files when applicable:

- `docs/WORKLOG.md`
  - every meaningful session
  - include exact commands and key results
- `docs/STATUS.md`
  - current state/blockers/next step changed
- `docs/features/*/progress.md`
  - progress, verification evidence, risks, next actions changed
- `docs/features/*/spec.md`
  - scope/approach/acceptance/verification strategy changed
- `docs/ADR/*.md`
  - cross-cutting decisions, durable tradeoffs, or exception policies changed

Prefer linking to canonical docs instead of copying the same guidance into multiple files.

## Exceptions and Escalation

If you must take a short-term compromise:

- document the exception (where it will be seen by the next engineer)
- explain why the compromise is necessary now
- define a removal trigger or follow-up condition
- assess risk and verification impact

Use an ADR when the exception changes team-level rules or affects multiple modules.

## Quick Verification Checklist

- Change type identified correctly
- Required commands/checks run
- Results captured with exact commands
- Risks/blockers documented
- Docs and links updated
- Next step stated
