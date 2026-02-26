# Feature Spec: UI/UX Rework

- Related Objectives: `OBJ-007`
- Status: in_progress

## Goal

Modernize the internal dashboard pages with a stronger, more polished UX and visual identity while preserving existing backend behavior and page functionality.

## Phase 1 Scope (Current)

### In

- `public/achievements.php` UI/UX rework
- PT-BR copy cleanup on the achievements page (labels, microcopy, filter text)
- Improved hover/focus/visual states for achievements cards and filters
- Recent achievements timeline UX (reward/celebration feel)
- Internal payload expansion for achievements page (`recent_unlocked`)
- Tests for new achievements payload shape/ordering limit

### Out

- `dashboard`, `habits`, `history` page rework (future phases)
- Search/sort beyond current filter controls
- Backend schema changes
- Global theme/token redesign

## UX Targets (Phase 1)

- Achievements page should feel complete (not placeholder/minimal)
- Recent unlocks should be presented in a rewarding timeline format
- Filters should provide clear active state and result feedback
- UI should remain responsive and keyboard-accessible
- User-facing text on the achievements page should be PT-BR

## Public Interfaces / Behavior Changes

- Achievements page payload now includes `data.recent_unlocked` (max 5 items, newest first)
- Existing `highlights` payload remains for compatibility during rollout
- Achievements gallery display order on the page is sorted from easier to harder (rarity/target/points)

## Verification Strategy

- `php -l` on changed PHP files
- `composer test:action` (payload/service changes)
- Manual achievements page smoke-check (filters, timeline, responsive, PT-BR copy) in a follow-up validation pass

## Future Phases (Planned)

1. `dashboard` visual polish and consistency pass
2. `habits` page interaction/spacing/controls redesign
3. `history` page charts/cards/filters consistency pass
4. Shared dashboard component extraction if repeated patterns stabilize
