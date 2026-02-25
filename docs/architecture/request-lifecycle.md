# Request Lifecycle

## Web Action Request (Typical Flow)

1. HTTP request hits an `actions/*.php` script
2. Action script `require_once`s `config/bootstrap.php`
3. `bootApp()` runs:
   - Composer autoload load
   - security headers/session config
   - session start
   - error handling init
   - DB connection setup (`$conn`)
4. Action adapter invokes domain/service logic (increasingly via handler classes)
5. Response is applied as redirect or JSON
6. Session flash messages are set when needed

## Notes on Side Effects

- `bootApp()` has runtime side effects by design
- redirects and JSON helpers can terminate execution (`exit`)
- tests avoid direct exit-heavy code by calling extracted handlers

