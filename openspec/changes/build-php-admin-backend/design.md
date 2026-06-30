## Context

The repository already contains a working Laravel backend, PostgreSQL-backed campaign domain, runtime APIs, and a first-pass admin area. That admin area proves the backend can authenticate operators and edit some game data, but it does not match the intended product behavior. The target experience is much closer to a guided builder: operators should move through explicit steps, adjust wheel-specific visuals, preview the result in context, and manage campaign operations without feeling like they are editing raw database fields.

The reference UI provided by the user has several defining characteristics:
- an application shell with left-side navigation and a dense top toolbar
- a stepper-based game editing workflow
- a split screen between editor controls and live preview
- wheel-design controls for quick palettes, border presets, pointer assets, and visual composition
- footer actions such as save, continue, and step navigation

This redesign must be layered onto the current backend rather than replacing it. The PHP admin should remain server-rendered where practical, but it needs richer interactivity than plain form pages currently provide.

## Goals / Non-Goals

**Goals:**
- Replace the current admin shell with a builder-oriented operator UI that visually aligns with the provided reference direction.
- Convert game editing into a step-based workflow for general settings, rewards, wheel design, and final game presentation.
- Provide a live preview that updates from operator-edited values before those values are committed to runtime publishing.
- Complete missing operator workflows for reward codes, submissions, spin results, claims, and publish-state controls.
- Extend persisted configuration so wheel-specific design choices are managed through admin and exposed safely to runtime APIs.

**Non-Goals:**
- Building a fully free-form page builder or drag-and-drop layout engine.
- Replacing the existing mini app runtime with a separate frontend stack in this change.
- Introducing a separate SPA admin unless server-rendered patterns prove insufficient for a specific builder island.
- Implementing advanced analytics, billing, or customer self-service onboarding in this change.

## Decisions

### 1. Keep Laravel as the admin host, but move to a product-shell layout

The admin should continue using Laravel web routes, Blade, and the existing auth stack. However, the visual shell must change from Breeze defaults to a bespoke product layout with:
- left navigation rail
- top toolbar
- content workspace
- stepper header for builder pages

This preserves velocity and keeps operational auth, policies, and audit logging in one place.

Alternatives considered:
- Full SPA rewrite: more flexible, but too much stack churn for the current phase.
- Keep Breeze layout and only restyle forms: too limited for the target operator experience.

### 2. Model the editor as a guided builder, not a flat CRUD form

The admin editing flow should be broken into clear steps:
1. General configuration
2. Reward configuration
3. Wheel design
4. Game presentation and publish review

Each step maps to a cohesive subset of the underlying domain and allows operators to save progress while maintaining context.

Alternatives considered:
- One long form: simpler technically, but poor usability and not aligned with the reference workflow.
- Separate unrelated pages per domain object: operationally awkward and weak for live preview.

### 3. Introduce wheel-design configuration as persisted structured data

The current data model stores theme and content, but not enough wheel-specific design information to power the target editor. The backend should introduce structured configuration for:
- palette preset selection
- wheel border preset or asset reference
- pointer preset or asset reference
- wheel center style or asset reference
- preview/layout tokens for slice presentation
- optional background asset references

These values should live in normalized tables or explicit JSON fields with predictable structure, not ad hoc frontend-only constants.

Alternatives considered:
- Keep wheel design hardcoded in frontend preview: would make the admin misleading and incomplete.
- Store everything in one untyped JSON blob: faster at first, but harder to validate and evolve safely.

### 4. Use server-rendered pages with targeted interactive islands for preview-heavy areas

Most admin screens can remain Blade-driven. The builder preview and wheel-design interactions may use lightweight interactive islands powered by Alpine, Livewire, or a small embedded frontend component. This gives enough responsiveness for preview without forcing a full admin rewrite.

The preview island should:
- read form state from the current step
- render wheel and content preview in real time
- support preset switching without page reload

Alternatives considered:
- Pure Blade only: inadequate for responsive preview interactions.
- Full client-rendered builder: more complex than needed for this stage.

### 5. Complete the admin around operator workflows, not just data entities

The missing admin functions should be implemented as operator-centric flows:
- reward code management with bulk import/review
- submissions with search/filter/export-ready structure
- spin result review with status context
- claim review and fulfillment actions
- publish controls and preview validation

This keeps the experience aligned with daily operations rather than exposing only low-level model editing.

Alternatives considered:
- Continue adding isolated CRUD pages one entity at a time: would fragment the operator journey.

## Risks / Trade-offs

- [Builder UI on top of server-rendered Laravel may become interaction-heavy] → Mitigation: isolate preview-rich regions so only those use richer client behavior.
- [New wheel-design fields could drift from runtime consumption] → Mitigation: define explicit runtime-safe mapping in the bootstrap contract and cover it with tests.
- [Reference UI may imply features not yet modeled in backend] → Mitigation: separate visual parity work from data-model parity and stage the rollout by builder step.
- [Completing every missing admin screen in one pass may increase scope] → Mitigation: prioritize the builder path first, then supporting operations screens in dependency order.
- [Persisting uploaded assets adds storage concerns] → Mitigation: begin with local/public storage abstractions and keep provider-specific storage decisions out of this change.

## Migration Plan

1. Replace the current admin shell and navigation with the new product layout.
2. Refactor the game edit flow into a builder route structure with step-aware controllers and views.
3. Add migrations for wheel-design configuration and any missing admin workflow metadata.
4. Implement the live preview and builder save flows step by step.
5. Add the remaining operator screens for reward codes, submissions, spin results, and claims.
6. Update runtime bootstrap shaping so published games reflect the builder-managed design fields.
7. Validate end-to-end with one sample workspace and one lucky wheel game before broader rollout.

Rollback strategy:
- Keep existing routes available behind the same auth boundary until the new builder routes are stable.
- Add new schema fields in backward-compatible fashion so current runtime behavior continues until the builder publishes new values.
- If the new builder causes issues, route operators back to the simpler edit screens while preserving the new data model.

## Open Questions

- Should asset upload be part of this change, or should the first pass use preset libraries plus URL-based assets?
- Does the operator need draft autosave per step, or is explicit save per step sufficient for the first iteration?
- Which preview interactions must be truly real-time versus refreshed on save within the first milestone?
- Should publish/unpublish remain a simple status toggle, or become a validation gate with missing-field checks?
