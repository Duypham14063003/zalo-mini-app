## Why

The lucky wheel builder currently lets operators choose a wheel border preset in configuration, but the admin experience does not present the actual border assets as selectable options and does not reliably preview the chosen border on the rendered wheel. That makes branding changes error-prone and forces users to guess how the final wheel will look.

This change is needed now because the product already has curated border assets in `backend/bg-vongquay/`, and operators need a direct visual workflow where selecting a border immediately updates both the admin preview and the runtime wheel configuration.

## What Changes

- Add a visual border preset picker to the lucky wheel design step that lists the available border assets from `backend/bg-vongquay/` as selectable options.
- Make the selected preset update the admin wheel preview immediately so operators can validate the final appearance before publishing.
- Ensure the selected border preset is persisted through draft, publish, and bootstrap flows so the runtime mini app uses the same wheel border asset.
- Align the runtime wheel rendering with the preset asset model instead of relying only on simplified CSS-only border styles.

## Capabilities

### New Capabilities
- `wheel-border-preset-preview`: Visual border preset selection for lucky wheel games, including admin preview binding and runtime asset delivery.
- `runtime-wheel-border-assets`: Runtime lucky wheel rendering that resolves the published border preset to the same asset family used in the admin preview.

### Modified Capabilities
- None.

## Impact

- Affects the Filament lucky wheel design form and its reactive preview behavior.
- Affects draft and publish serialization for wheel border configuration.
- Affects the bootstrap API payload for wheel theme metadata.
- Affects the frontend lucky wheel renderer so the runtime wheel uses the chosen preset asset consistently.
