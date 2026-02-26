# ADR-0004: Achievements/progression overhaul with reward unlocks and custom-frequency removal

- Status: accepted
- Date: 2026-02-26

## Context

The legacy achievements implementation had three major limitations:

- perfect-day achievements counted all active habits, which broke weekly/custom schedule scenarios
- achievement persistence stored only unlock rows with limited progress context (`user_achievements`)
- XP/level progression was derived only from achievement points with a hidden formula and no level rewards

Additionally, `custom` habit frequency duplicated `weekly` behavior (same `target_days` concept) while increasing UI, validation, schedule, and test complexity.

## Decision

- Remove `custom` habit frequency from the product model and schema (`habits.frequency` now supports only `daily` and `weekly`)
- Introduce a new achievements data model:
  - `achievement_definitions`
  - `user_achievement_unlocks`
  - `user_achievement_events`
- Refactor achievements sync to evaluate rule-driven definitions and persist unlock events idempotently
- Define perfect-day semantics as:
  - scheduled-only (daily/weekly schedule respected per date)
  - days with zero scheduled habits do not count as perfect and break the streak
- Introduce a progression/rewards model:
  - `progression_levels`
  - `reward_definitions`
  - `user_reward_unlocks`
  - `user_reward_events`
- Recompute XP from:
  - habit completions (base XP)
  - achievement unlock bonus XP
- Persist `users.level` and `users.experience_points` as snapshots for UI compatibility
- Grant level milestone rewards as persistent profile badges (v1)

## Consequences

### Positive

- Fixes perfect-day achievement correctness for scheduled habits
- Makes achievements easier to extend with new rule types without hardcoding one-off logic per achievement
- Adds audit/timeline-ready event history for achievements and rewards
- Creates a clearer, more motivating progression system (completion XP + achievement XP)
- Unlockable reward infrastructure supports future cosmetic/content rewards beyond profile badges
- Simplifies habit scheduling by removing the redundant `custom` frequency path

### Negative / Cost

- Significant schema and service complexity increase versus the legacy achievement implementation
- Existing achievement state is no longer represented by the legacy tables used in older views/helpers
- More DB writes during sync (unlock + event recording)
- Existing environments need schema migration / reset to use the new tables and seeds

## Alternatives Considered

- Minimal bug fix only (perfect-day scheduled count) without schema redesign
  - Rejected: would not address extensibility, XP progression clarity, or reward unlock requirements
- Keep achievements in legacy `achievements` / `user_achievements` tables only
  - Rejected: poor fit for rule metadata and unlock/event history
- Keep `custom` frequency and patch only achievement logic
  - Rejected: unnecessary schedule-mode duplication remains across UI/validation/tests

## Links

- `app/Achievements/AchievementService.php`
- `app/UserProgress/UserProgressService.php`
- `app/Repository/AchievementRepository.php`
- `app/Api/Internal/AchievementsApiPayloadBuilder.php`
- `sql/doitly_unified.sql`
- `public/includes/profile_modal.php`
