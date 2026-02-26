# Development Roadmap (Engineering-Facing)

This roadmap complements the GitHub-facing project overview in `README.md` by focusing on engineering enablement and delivery reliability.

## Completed Foundations (Done)

- `OBJ-001` Local environment standardization (PHP/Composer/MySQL compatibility)
- `OBJ-002` Automated testing foundation (PHPUnit + MySQL test reset workflow)
- `OBJ-003` Critical-flow coverage expansion (Phase 2A-2F)

## Current Focus (Near-Term)

- Sustain and harden docs freshness/adoption across sessions (`OBJ-004`)
- Standardize remaining procedural actions on handler/adaptor patterns (`OBJ-005`)
- Evaluate and introduce CI workflow for `composer test` (optionally `composer qa`) now that local suites are stable

## Next (Mid-Term)

- Improve observability and error diagnostics (`OBJ-006`)
- Expand runbooks for recurring failure/debug scenarios
- Strengthen release and migration discipline

## Later (Long-Term / Optional)

- Documentation automation/checks (freshness or link validation)
- Broader engineering automation around repeatable verification workflows

## Milestone Mapping (Updated)

- M1: Local environment and test execution unblocked (`OBJ-001`)
- M2: Testing foundation verified and documented (`OBJ-002`)
- M3: Coverage expansion completed for critical flows (`OBJ-003`)
- M4: Docs-system operational consistency + action-pattern standardization (`OBJ-004`, `OBJ-005`)
- M5: CI and observability improvements (`OBJ-006` + CI follow-through)
