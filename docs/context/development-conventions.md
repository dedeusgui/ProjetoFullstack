# Development Conventions (Repo-Specific)

This file covers implementation conventions specific to this repository.

Use `docs/standards/engineering-handbook.md` for project-wide quality policy:
- clean code expectations
- SOLID guidance
- review checklist
- verification/test gates
- definition of done

## Scope of This File

Keep this document limited to repo-specific patterns and migration conventions. Do not duplicate generic engineering standards here.

## Loading Pattern (Composer Autoload vs `require_once`)

This project intentionally uses both patterns for different purposes:

- `require_once` is used in entry scripts (`actions/*.php`, `public/*.php`) to load `config/bootstrap.php`
- `vendor/autoload.php` (Composer autoload) is loaded inside `bootApp()` to autoload classes and helper files configured in Composer

This is not inherently inconsistent because:
- entry scripts need explicit bootstrap side effects (session, headers, error handling, DB)
- application classes should be resolved by Composer autoload

### Actual Inconsistency to Watch For

- Manually requiring app classes that Composer can autoload
- Bypassing `bootApp()` in web entrypoints without a documented reason

## Action Pattern (Current Direction)

- Keep `actions/*.php` as HTTP adapters/entrypoints
- Extract reusable/testable logic into `app/Actions/*` handlers
- Convert handler output into redirects/JSON through a shared adapter (`actionApplyResponse`)

## Documentation Pattern

- Use objective IDs (`OBJ-xxx`) in worklogs and feature progress
- Create ADRs for cross-cutting decisions
- Update `STATUS.md` at the end of meaningful sessions
- Use `docs/README.md` canonical source map before adding a new top-level doc
