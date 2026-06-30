## Why

The backend foundation and basic admin CRUD now exist, but the operator experience is still far from the intended product. Instead of a real game-builder interface, the current admin behaves like a generic Laravel dashboard with partial editing screens, limited management workflows, and no step-based preview experience for designing a lucky wheel campaign.

## What Changes

- Re-scope the PHP admin from a simple campaign CRUD console into a guided game-builder experience tailored to the lucky wheel template.
- Replace the current Breeze-style admin shell with a product-style layout that includes a left navigation rail, top toolbar, stepper workflow, and a persistent editing workspace.
- Redesign the game editing flow into a multi-step builder for general configuration, reward configuration, wheel design, and final game presentation.
- Add a real-time preview panel so operators can see wheel, theme, content, and prize changes before saving or publishing.
- Complete the missing admin workflows for reward codes, player submissions, spin histories, claim records, and publish-state operations.
- Extend persisted game configuration so wheel-specific visual choices such as presets, border assets, pointer assets, and preview tokens can be managed from admin instead of being implied in frontend code.

## Capabilities

### New Capabilities
- `campaign-admin`: Product-style admin builder for configuring, previewing, publishing, and operating lucky wheel games.
- `campaign-data-platform`: Persisted builder configuration, wheel-design metadata, and operational records required by the admin.
- `spin-engine-api`: Runtime behavior that reflects builder-managed configuration and operator-driven publication state.

### Modified Capabilities
- None.

## Impact

- Affects Laravel web routes, controllers, Blade layouts, and admin-specific UI flows in `backend/`.
- Requires data model extensions for wheel-design metadata, admin workflow state, and richer operational views.
- Impacts runtime bootstrap behavior because the mini app must reflect admin-managed builder configuration.
- Reuses the existing PHP/PostgreSQL/Docker foundation rather than introducing a new backend stack.
