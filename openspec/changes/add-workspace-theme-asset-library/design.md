## Context

The current lucky wheel builder stores some visual settings directly in game draft and theme records, but preset discovery is still hardcoded in PHP helpers and does not support a workspace-owned media library. Background and wheel border already have partial preset concepts, while banner, spin button, extra-spin button, and image-based pointer selection are not yet modeled as reusable workspace assets.

The requested experience introduces two separate responsibilities:
- workspace owners curate approved presets once
- game editors compose a specific game's visuals by choosing presets or uploading overrides

That split affects data modeling, permissions, preview rendering, publish serialization, and runtime bootstrap delivery. The design must preserve workspace scoping, reuse the existing draft/publish flow, and keep uploaded per-game overrides higher priority than presets.

## Goals / Non-Goals

**Goals:**
- Introduce a workspace-scoped theme asset library managed by workspace owners.
- Support the following slot types: `background`, `banner`, `spin_button`, `extra_spin_button`, `wheel_border`, and `wheel_pointer`.
- Let game editors select a preset or upload an override for each supported slot.
- Keep game draft, published config, and runtime bootstrap in sync for all slot types.
- Show accurate preview behavior in both the wheel-design step and the full game-design step.
- Render the extra-spin button only when a player has no spins remaining.

**Non-Goals:**
- Rebuild the broader game builder IA beyond the new asset-library and slot-binding flows.
- Add rich banner composition, text overlays, or animated banners.
- Support non-image pointer generation modes for the new asset-library flow.
- Replace existing reward, redirect, or gameplay rules unrelated to visual theme assets.

## Decisions

### 1. Introduce a dedicated workspace theme asset table

The system will add a workspace-scoped asset library model instead of continuing to hardcode preset catalogs in resource classes.

The new record shape should include:
- `workspace_id`
- `asset_type`
- `name`
- `asset_path`
- `mime_type`
- `is_active`
- `sort_order`
- `meta`

Why:
- Theme presets must be manageable through the admin UI by workspace owners.
- A dedicated table keeps workspace isolation explicit and queryable.
- The same storage model can support current and future slot types without requiring new columns for each visual asset class.

Alternative considered:
- Keep storing curated presets as PHP arrays and only add more hardcoded lists.
  - Rejected because owners must be able to add/change assets from the web UI.

### 2. Keep game-level slot bindings in draft/published config, not in separate normalized tables

Each game will continue to store design choices in `draft_config`, `published_config`, and runtime theme synchronization, but the slot payloads will grow to include preset ids plus override paths.

Expected slot payload structure:

```json
{
  "background_preset_id": 12,
  "background_asset_path": "wheel-backgrounds/custom-bg.png",
  "banner_preset_id": 18,
  "banner_asset_path": null
}
```

Why:
- The existing game builder already persists design decisions through JSON draft/publish payloads.
- Extending the current structure avoids creating a second configuration source for per-game state.
- Slot bindings stay versionable alongside other draft design settings.

Alternative considered:
- Normalize each slot selection into a separate relational table.
  - Rejected because it adds implementation overhead without clear value for the current builder architecture.

### 3. Resolve presets through one backend service layer

Preset lookup, fallback ordering, and runtime URL resolution should be centralized in a backend service/helper layer instead of duplicated across Filament resources and frontend code.

Resolution order per slot:
1. Per-game uploaded override
2. Selected workspace preset
3. Built-in fallback, if the slot still has a legacy default

Why:
- The same precedence rules must be used in admin preview, publish synchronization, and bootstrap serialization.
- Centralizing resolution reduces drift between preview and runtime.

Alternative considered:
- Let each controller/resource resolve assets ad hoc.
  - Rejected because the number of slots and precedence branches will grow quickly.

### 4. Split design controls into wheel slots and game-shell slots

The admin builder should separate:
- wheel-focused slots: palette, border, pointer
- game-shell slots: background, banner, spin button, extra-spin button

Why:
- This matches the mockup and keeps the mental model clear.
- The preview area can then reflect the correct part of the mobile layout for each slot group.

Alternative considered:
- Keep everything in one long generic media section.
  - Rejected because it makes slot-to-preview mapping harder to understand.

### 5. Treat banner as a static image and wheel pointer as PNG-only

The new library will store banner as a plain image asset and validate wheel pointer uploads/presets as PNG only.

Why:
- These requirements are explicitly confirmed.
- Static banner keeps the first implementation tractable and avoids text layout complexity.
- PNG-only pointer assets reduce ambiguity around transparency and rendering quality.

Alternative considered:
- Allow SVG/JPG/webp pointer assets.
  - Rejected for the first version to keep validation and runtime rendering predictable.

### 6. Render the extra-spin button only in zero-spin state

The extra-spin button slot will not appear as a permanent CTA. It will only render when `remainingSpins` reaches zero in the runtime post-spin flow.

Why:
- This directly matches the confirmed requirement.
- The current runtime already has a remaining-spins branch in the result overlay, so the new CTA belongs there naturally.

Alternative considered:
- Render the extra-spin button permanently below the wheel.
  - Rejected because it weakens the meaning of “extra spin” and adds noise while spins remain.

## Risks / Trade-offs

- [Workspace library adds a new admin surface and permission boundary] → Mitigation: scope CRUD access to workspace owners/admins only and derive workspace context from existing membership rules.
- [Game config payload size grows with multiple asset slots] → Mitigation: store ids and relative asset paths only, not expanded file metadata blobs.
- [Preview and runtime can drift if slot resolution is duplicated] → Mitigation: centralize slot resolution and reuse the same resolved payload shape for preview/bootstrap.
- [Legacy hardcoded presets and new library assets can overlap awkwardly during migration] → Mitigation: define explicit built-in fallback behavior and migrate curated defaults into seed/library records where possible.
- [Per-slot uploads increase storage usage] → Mitigation: keep uploads scoped to slot-specific directories and rely on existing Laravel storage/public disk behavior.

## Migration Plan

1. Add a workspace-scoped theme asset table and model with slot typing and ordering.
2. Seed or backfill the currently curated assets into the new library where appropriate.
3. Add owner-facing CRUD UI for library management.
4. Extend game design draft/publish payloads with preset-id plus override-path fields for each supported slot.
5. Update preview builders to resolve slot visuals through the new asset library.
6. Extend runtime bootstrap and frontend rendering for all supported asset slots.
7. Verify zero-spin behavior for extra-spin CTA and preview/runtime parity for each slot type.

Rollback:
- Remove the new workspace asset bindings from game design payloads, revert to hardcoded preset catalogs, and hide the owner-facing asset-library UI.

## Open Questions

- Should built-in starter assets remain on disk as immutable fallbacks, or should they be imported into the workspace asset library during seeding?
- Should the extra-spin button trigger a configurable action in this same change, or initially reuse the existing claim/continue action framework until a dedicated flow is defined?
