# Development Conventions

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

