## Context

The lucky wheel builder already stores `design.border_preset` through draft and publish flows, but the admin form still behaves like a generic select field and the preview experience is not driven by the actual border image assets in `backend/bg-vongquay/`. At the same time, the runtime mini app still treats wheel borders mostly as CSS style variants rather than as a shared asset-backed preset system.

This creates a mismatch across four layers:
- admin form selection
- admin preview rendering
- published bootstrap payload
- runtime lucky wheel rendering

The change must preserve the current project structure, reuse the existing border assets in `backend/bg-vongquay/`, and avoid introducing a new admin surface outside the current Filament lucky wheel design step.

## Goals / Non-Goals

**Goals:**
- Present all available wheel border assets as a visual preset list in the Filament lucky wheel design step.
- Make selecting a border preset update the admin preview immediately without waiting for publish.
- Persist the chosen preset through draft, publish, and bootstrap flows.
- Render the runtime lucky wheel with the same asset family that the admin preview uses.
- Keep border preset metadata centralized so admin and runtime do not drift over time.

**Non-Goals:**
- Reworking the entire lucky wheel design form layout.
- Replacing every existing color or pointer preset interaction in the same change.
- Introducing a full media-library workflow for border presets beyond the existing curated asset list.
- Removing support for custom uploaded outer-border images if that path already exists.

## Decisions

### 1. Treat curated border presets as explicit asset-backed metadata

The system will maintain one border preset catalog that maps each preset id to:
- display label
- source asset path in `backend/bg-vongquay/`
- preview asset representation
- runtime asset representation

Why:
- The current CSS class-only approach is too lossy for image-based borders.
- A single catalog reduces the chance that Filament preview and runtime wheel use different visuals.

Alternative considered:
- Keep CSS classes only and approximate each border in preview/runtime.
  - Rejected because the user explicitly wants the actual provided border images to be selectable and previewed.

### 2. Use a visual picker instead of a plain select for border presets

The Filament design step will replace or wrap the current `border_preset` input with a card/grid picker that shows each wheel border asset as a selectable thumbnail. The underlying saved value remains the preset id, not the raw file path.

Why:
- The operator needs to recognize the border visually, not by name only.
- Preset ids remain stable even if asset filenames evolve later.

Alternative considered:
- Keep the select and add a separate static image legend below it.
  - Rejected because it adds cognitive load and does not make the selection flow visual-first.

### 3. Make preview rendering depend on reactive form state, not on a static placeholder snapshot

The admin preview should derive its border rendering from the same live form state that stores `border_preset`. Whether implemented through a reactive Filament component or a state-bound Blade view, the preview must re-render when the selected preset changes.

Why:
- The current failure mode is that the selection can change without the preview image updating.
- A direct reactive dependency is easier to reason about than duplicated local UI state.

Alternative considered:
- Update preview only after save.
  - Rejected because the requirement is to preview before publishing and while choosing.

### 4. Publish runtime metadata in a form that supports asset-based rendering

The bootstrap response will expose enough wheel metadata for the frontend to resolve the published border preset to the same border asset used in admin preview. This may be either:
- a concrete asset URL, or
- a preset id plus deterministic client-side mapping to a packaged asset

Preferred direction:
- Send asset-oriented metadata from backend bootstrap so runtime mirrors the published config without duplicating mapping logic across clients.

Why:
- It keeps the backend as the source of truth for published wheel styling.
- It makes future asset changes safer because the client receives the resolved runtime input.

### 5. Preserve custom upload override behavior as higher priority than curated preset

If a custom outer-border upload is already configured for a game, runtime and preview should prefer that uploaded asset over the curated preset. The preset still acts as the fallback/default path.

Why:
- This respects the more specific operator customization without removing the curated preset workflow.

Alternative considered:
- Disable custom uploads when presets exist.
  - Rejected because the current product direction still wants both curated defaults and customer-specific overrides.

## Risks / Trade-offs

- [Reactive preview logic differs between Filament field state and custom picker state] → Mitigation: bind the picker to the canonical form state and make the preview read only from that state.
- [Backend asset paths such as `backend/bg-vongquay/*.png` are not web-safe runtime URLs by default] → Mitigation: introduce one explicit asset-resolution step for preview/runtime instead of exposing raw filesystem paths.
- [Curated asset mapping can drift if duplicated in multiple files] → Mitigation: keep one preset catalog and reuse it in admin picker, preview, and bootstrap serialization.
- [Custom upload override can mask preset changes and confuse operators] → Mitigation: display a clear helper note in admin when an uploaded border is taking precedence over the preset.
- [Legacy Blade builder and Filament builder can diverge] → Mitigation: scope this change to the active Filament flow and keep any remaining legacy view behavior clearly non-authoritative.

## Migration Plan

1. Introduce or consolidate a border preset catalog for the curated `bg-vongquay` assets.
2. Replace the border preset selection UX in the Filament design step with a visual picker bound to live form state.
3. Update the admin preview renderer so border changes are reflected immediately from form state.
4. Update publish/bootstrap serialization so runtime receives consistent border preset metadata or resolved asset URLs.
5. Update frontend wheel rendering to consume the published border asset representation.
6. Verify draft save, publish, bootstrap response, and runtime preview for each curated border option.

Rollback:
- Revert to the prior select-based `border_preset` field and CSS class-driven runtime border rendering while retaining the saved preset ids.

## Open Questions

- Should the runtime consume resolved backend URLs for curated border assets, or should those assets be bundled with the frontend and selected by preset id?
- Do we want the admin preview to show both “preset currently selected” and “custom upload override active” at the same time when both values exist?
