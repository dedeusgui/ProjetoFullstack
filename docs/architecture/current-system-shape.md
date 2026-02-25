# Current System Shape

## Architectural Style

Layered PHP application with procedural HTTP entrypoints and service/repository classes:

`public -> actions -> app services -> repositories -> MySQL`

## Key Characteristics

- Web-facing entrypoints are procedural scripts
- Business logic is increasingly being moved into classes
- Helper functions in `config/` provide cross-cutting concerns
- DB access uses MySQLi and repository classes (plus some direct queries in services)
- SQL schema/procedures are maintained in one unified dump file

## Current Transition in Progress

The codebase is moving toward a more testable action pattern:
- thin action scripts as adapters
- handler classes in `app/Actions/*`
- shared response translation helper (`actionApplyResponse`)

This transition is partial and should be expanded intentionally (tracked by `OBJ-005`).

