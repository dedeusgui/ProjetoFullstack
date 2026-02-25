# Codebase Map

## Runtime Entry Points

### Public Pages

- `public/index.php`
- `public/login.php`
- `public/register.php`
- `public/dashboard.php`
- `public/habits.php`
- `public/history.php`

### HTTP Actions

- `actions/habit_create_action.php`
- `actions/habit_update_action.php`
- `actions/habit_toggle_completion_action.php`
- other auth/profile/export actions

## Bootstrap / Infrastructure

- `config/bootstrap.php`
  - Loads Composer autoload
  - Applies headers and session setup
  - Initializes error handling
  - Loads DB connection
- `config/database.php`
  - MySQLi connection using environment variables
- `config/*_helpers.php`
  - procedural helpers for auth, actions, security, errors

## Application Layers (`app/`)

- `app/Habits/*`: habit domain logic and services
- `app/Repository/*`: DB access repositories
- `app/Recommendation/*`: behavior/trend/scoring logic
- `app/Support/*`: cross-cutting helpers (formatting, time, request context, exceptions)
- `app/Actions/*`: extracted testable action handlers / response objects (newer pattern)

## Testing

- `tests/Unit/*`: pure logic tests
- `tests/Action/*`: handler/action behavior tests
- `tests/Support/*`: DB reset/import, fixtures, request state, base test cases

