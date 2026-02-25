# Runbook: PHP Upgrade Checklist

## Purpose

Safely upgrade local PHP and validate project compatibility.

## Checklist

- [ ] Install/enable target PHP version (8.2+)
- [ ] Confirm CLI version with `php -v`
- [ ] Confirm web server PHP version (if using XAMPP)
- [ ] Ensure `mysqli` extension is enabled
- [ ] Ensure `openssl` extension is enabled (required by Composer TLS)
- [ ] Ensure `mbstring` extension is enabled (required by PHPUnit)
- [ ] Ensure `curl` extension is enabled (Composer performance/network support)
- [ ] Ensure `pdo_mysql` extension is enabled (recommended alongside `mysqli`)
- [ ] Ensure Composer uses the updated PHP binary
- [ ] Run `composer install`
- [ ] Run `composer test:db:reset`
- [ ] Run `composer test:unit`
- [ ] Run `composer test:action`
- [ ] Record results in `docs/WORKLOG.md`

## Common Pitfalls

- CLI PHP and web server PHP are different versions
- Global `phpunit` is old and shadows project usage
  - use Composer script or `vendor/bin/phpunit`
- Missing extensions after upgrade (`mysqli`, `openssl`, `mbstring`, etc.)
- Composer fails with TLS error because `openssl` is disabled
- PHPUnit install fails because `mbstring` is disabled
- Test DB reset fails with `Class "mysqli" not found` because `mysqli` is disabled
