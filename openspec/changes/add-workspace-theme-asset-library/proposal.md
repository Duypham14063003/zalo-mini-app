## Why

The lucky wheel builder currently mixes hardcoded visual presets with a few per-game uploads, which makes theme management inconsistent and prevents workspace owners from preparing reusable branded assets for their teams. Operators need a single workflow where owners provide approved visual themes and game editors can either choose those presets or upload a game-specific override.

This change is needed now because the builder already supports curated border and background concepts, but the desired product experience requires a complete workspace-managed asset library for game background, top banner, spin button, extra-spin button, wheel border, and wheel pointer PNG assets.

## What Changes

- Add a workspace-scoped theme asset library that lets workspace owners upload and manage reusable visual assets for supported game design slots.
- Extend game design configuration so editors can choose a workspace preset or upload an override for each supported slot.
- Expand admin preview behavior so the selected preset or uploaded override updates the corresponding part of the preview immediately.
- Expand publish and runtime bootstrap payloads so the mini app receives resolved theme asset metadata for all supported slots.
- Add runtime rendering rules for asset-backed background, top banner, spin button, extra-spin button, wheel border, and wheel pointer visuals.

## Capabilities

### New Capabilities
- `workspace-theme-asset-library`: Workspace owners can manage reusable visual asset presets for lucky wheel game design slots.
- `game-theme-asset-binding`: Game editors can bind design slots to workspace presets or per-game uploaded overrides, preview the result, and publish runtime-ready theme assets.

### Modified Capabilities
- None.

## Impact

- Affects admin data modeling for workspace-scoped design assets and storage paths.
- Affects Filament admin surfaces for owner-managed preset libraries and game design forms.
- Affects draft and published game builder configuration payloads.
- Affects bootstrap API theme metadata and runtime asset resolution.
- Affects frontend lucky wheel rendering for shell background, banner, wheel chrome, and CTA button visuals.
