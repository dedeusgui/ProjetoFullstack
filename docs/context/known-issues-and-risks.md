# Known Issues and Risks

## Environment / Tooling

- Local PHP version compatibility may block test execution until upgrade is complete.
- Dev dependencies may be missing if `composer install` has not been run after updating `composer.json`.
- Network access to Packagist may be unavailable in some environments, blocking dependency installation.

## Testing Rollout Risks

- First-wave tests exist but full validation is pending local environment readiness.
- MySQL/MariaDB version differences may affect stored procedures/views in `sql/doitly_unified.sql`.
- Action behavior relies on redirects/session semantics; regressions can occur if adapters are changed without tests.

## Documentation Process Risk

- `docs/` can become stale if session logging is skipped.
- Mitigation: follow the session-end checklist in `docs/README.md`.

