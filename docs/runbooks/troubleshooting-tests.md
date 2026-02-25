# Runbook: Troubleshooting Tests

## Symptom: `vendor/bin/phpunit` not found

### Cause

Dev dependencies are not installed.

### Fix

- Run `composer install`
- If offline/network blocked, document the blocker and retry when network is available

## Symptom: Composer TLS / SSL error during install

### Cause

PHP CLI `openssl` extension is disabled.

### Fix

- Enable `extension=openssl` in the CLI `php.ini`
- Verify with `php --ri openssl`
- Retry `composer install`

## Symptom: PHPUnit cannot be installed (`ext-mbstring` missing)

### Cause

PHP CLI `mbstring` extension is disabled.

### Fix

- Enable `extension=mbstring` in the CLI `php.ini`
- Verify with `php --ri mbstring`
- Retry `composer install`

## Symptom: Composer uses old global PHPUnit

### Cause

Calling `phpunit` directly may resolve to a global XAMPP/PEAR binary.

### Fix

- Use Composer scripts (`composer test:*`)
- Or call `php vendor/bin/phpunit ...`

## Symptom: DB reset/import fails

### Cause

- MySQL/MariaDB not running
- Invalid credentials
- Missing DB create/drop permissions
- SQL compatibility issue
- `mysqli` extension disabled in PHP CLI

### Fix

- Verify connection env vars
- Check DB server version
- Inspect the failing SQL preview from the reset script
- Verify `php --ri mysqli` and enable `extension=mysqli` if missing

## Symptom: Action tests fail inconsistently

### Cause

- Shared DB state or session globals leaking across tests
- Time/date assumptions not controlled

### Fix

- Confirm base test cases reset DB/session state properly
- Add explicit fixtures and deterministic dates in tests
