## 1. Border Preset Catalog And Admin Selection

- [x] 1.1 Define a single curated border preset catalog for the `backend/bg-vongquay/` assets, including stable preset ids and display labels.
- [x] 1.2 Replace the Filament `border_preset` text/select interaction with a visual picker that renders each curated border asset as a selectable option.
- [x] 1.3 Bind the visual picker to the canonical design form state so the selected preset id is stored consistently in draft data.

## 2. Admin Preview Reactivity

- [x] 2.1 Update the admin lucky wheel preview renderer to resolve the selected border preset through the curated asset catalog.
- [x] 2.2 Make the admin preview re-render immediately when the selected border preset changes.
- [x] 2.3 Preserve custom uploaded border behavior as the higher-priority preview source while keeping the curated preset as fallback.

## 3. Publish And Runtime Delivery

- [x] 3.1 Update draft, publish, and merged preview serialization so the chosen border preset remains part of the lucky wheel design payload.
- [x] 3.2 Extend the bootstrap API to expose runtime-ready border asset metadata for the published wheel configuration.
- [x] 3.3 Update the runtime lucky wheel renderer to consume the published border asset metadata and render the same border family shown in admin preview.

## 4. Verification

- [x] 4.1 Add or update automated tests covering preset persistence from design save through bootstrap response.
- [ ] 4.2 Verify manually that each curated border option updates the admin preview and that published runtime wheels match the selected border preset.
