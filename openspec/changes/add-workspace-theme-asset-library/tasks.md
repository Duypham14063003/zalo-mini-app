## 1. Workspace Asset Library Foundation

- [x] 1.1 Add a workspace-scoped theme asset data model, migration, and storage conventions for supported slot types.
- [x] 1.2 Seed or register existing built-in default assets so the new library has usable starter presets.
- [x] 1.3 Add owner/admin permissions and CRUD surfaces for managing workspace theme assets.

## 2. Game Design Slot Binding

- [x] 2.1 Extend game draft and published design payloads with preset-id and override-path fields for background, banner, spin button, extra-spin button, wheel border, and wheel pointer slots.
- [x] 2.2 Update the game builder forms so editors can select workspace presets or upload overrides for each supported slot.
- [x] 2.3 Update admin previews so each slot change immediately updates the correct preview region.

## 3. Publish And Runtime Resolution

- [x] 3.1 Implement centralized slot resolution that prefers per-game overrides, falls back to workspace presets, and then to built-in defaults where needed.
- [x] 3.2 Extend bootstrap theme payloads to return resolved runtime-ready assets for all supported slots.
- [x] 3.3 Update the mini app renderer to use asset-backed background, banner, spin button, extra-spin button, wheel border, and wheel pointer visuals.
- [x] 3.4 Render the extra-spin button only when the current player has zero remaining spins.

## 4. Verification

- [x] 4.1 Add automated coverage for workspace preset visibility, draft/publish persistence, and bootstrap asset resolution.
- [ ] 4.2 Verify manually that preset selection and uploaded overrides both preview correctly and match published runtime output.
