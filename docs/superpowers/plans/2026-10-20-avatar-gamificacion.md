# Avatar y gamificación · Implementation Plan

> **For agentic workers:** Use the approved spec and the independent file scopes below. Observe failing tests before implementing behavior, then run the project suite and an independent whole-branch review.

**Goal:** Deliver a working, mobile-friendly preview of the VisionFit character experience.

**Architecture:** Derive progression from existing client logs in one service. Share lightweight Blade/SVG components between the authenticated app and a synthetic public demo. Activate the experience only with an environment flag.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade, Livewire/Alpine, plain CSS/JS and SVG; no new dependency or migration.

**Spec:** `docs/superpowers/specs/2026-10-20-avatar-gamificacion.md`

## Global Constraints

- Branch `feature/avatar-gamificacion`; preview only, no merge to main.
- No schema changes or production data copy.
- Feature flag `VISIONFIT_EXPERIENCE_ENABLED` defaults false.
- XP = 100 per local training date; level step 500.
- Canonical muscle IDs and shared view/service contracts come from the spec.
- Personalization stored locally per account; no biometric feature.
- Accessible at 360 px, reduced motion supported, no new dependencies.

## Review Focus

- Another client's logs and soft-deleted exercises must not leak or break summaries.
- Duplicate, edited, future and invalid old logs must not inflate XP.
- A replaced exercise must drive its own muscle map and progress history.
- Disabled feature and anonymous visitors must not receive real account data.
- Empty history and blocked localStorage must still render usable navigation.

## Task 1 · Domain data and muscle map

Files: create `app/Support/MuscleMap.php`, `app/Services/TrainingExperience.php`, `tests/Feature/TrainingExperienceTest.php`, `tests/Unit/MuscleMapTest.php`.

Interfaces: produce the exact service and muscle-map arrays in the spec; consume existing ExerciseLog/Assignment/Exercise relations.

- [ ] Write failing tests for own/foreign history, daily XP, level boundaries, empty and invalid data, actual exercise fallback, unknown muscle map and common Spanish aliases.
- [ ] Run those tests and observe missing feature failures.
- [ ] Implement the two focused classes without schema or model changes.
- [ ] Run domain tests, then the complete PHP suite.

## Task 2 · Shared visual experience

Files: create `resources/views/experience/overview.blade.php`, `resources/views/experience/character.blade.php`, `resources/views/components/client/muscle-avatar.blade.php`, `public/css/visionfit/experience.css`, `public/js/visionfit/experience.js`, `tests/js/experience.test.mjs`.

Interfaces: consume the shared view array and muscle IDs from the spec; root owns routing/controllers and includes these assets only on enabled pages.

- [ ] Write JS tests for supported avatar/theme choice, locked theme, blocked storage, independent account keys and demo counter capping.
- [ ] Observe red before implementing JS state.
- [ ] Build reusable SVG character and responsive dashboard/character views with synthetic demo interactions.
- [ ] Keep all links functional via provided URLs; no inline onclick or duplicate Alpine.
- [ ] Run JS suite and review visuals at mobile and desktop widths.

## Task 3 · App integration and preview deployment

Files: create `config/experience.php`, `app/Http/Controllers/Client/ExperienceController.php`, demo layout/data provider and `tests/Feature/ExperiencePagesTest.php`; modify dashboard/progress controllers, workout view, client layout and routes as needed.

Interfaces: consume tasks 1/2 contracts and the existing current performed exercise. Routes: `/experiencia` public demo, `/experiencia/personaje` public character demo, `/app/personaje` authenticated profile.

- [ ] Write integration tests: disabled404, demo without users, own-user data, role boundary, enabled dashboard/profile, unknown muscle fallback and escaped user title.
- [ ] Observe red then implement feature gating and route/view integration.
- [ ] Run full PHP/JS tests, Pint on touched PHP, build, and independent review.
- [ ] Publish branch, create draft PR and deploy only existing preview web service against preview MySQL.
- [ ] Observe terminal SUCCESS; verify public demo and real routes' authorization; deliver preview and rollback instructions.
