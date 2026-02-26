# ADR Index

Architectural Decision Records (ADRs) used by this project.

| ID | Title | Status | Date | Summary |
| --- | --- | --- | --- | --- |
| ADR-0001 | Docs system and objective-linked progress logging | accepted | 2026-02-25 | Centralizes development context and requires session logging linked to future objectives. |
| ADR-0002 | Action handler extraction pattern for testability | accepted | 2026-02-25 | Keeps `actions/*.php` as adapters while moving behavior into testable handlers. |
| ADR-0003 | PHPUnit + MySQL test schema strategy | accepted | 2026-02-25 | Uses PHPUnit and a dedicated MySQL test DB for action/integration tests. |
| ADR-0004 | Achievements/progression overhaul with reward unlocks and custom-frequency removal | accepted | 2026-02-26 | Replaces legacy achievement storage with definition/unlock/event tables, adds XP levels + profile badge rewards, and removes `custom` habit frequency. |

## Usage Notes

- Link ADRs from `docs/WORKLOG.md` and feature docs when relevant.
- Create a new ADR when a cross-cutting technical decision affects architecture, testing, runtime, or conventions.
