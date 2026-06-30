## Why

Publishing a game currently only moves builder configuration into the runtime tables. Operators still have no deployment-ready output they can hand to customers, because publishing does not generate a stable launch link, a Zalo-facing entry path, or a QR code payload that opens the correct game inside the shared Mini App.

The platform is now far enough along that launch distribution has become the next operational gap. Without a first-class publish-to-launch flow, every campaign still requires manual coordination outside the product, which blocks the service model the backend is supposed to enable.

## What Changes

- Add a launch-generation workflow that runs when a lucky wheel game is published and produces a reusable public game entry for distribution.
- Persist launch metadata separately from the core game record so each game can track runtime URLs, Mini App entry paths, Zalo launch URLs, QR payloads, and channel readiness.
- Extend the publish experience in admin so operators can immediately view, copy, regenerate, and validate deployment links after publishing.
- Add explicit distinction between internal publication state and Zalo launch readiness so a game can be published in runtime while still being flagged as incomplete for external distribution.
- Prepare the runtime frontend contract to resolve a published game from a stable public identifier supplied through a Mini App path or query payload.

## Capabilities

### New Capabilities
- `game-launch-links`: Persisted launch records for published games, including public identifiers, runtime URLs, Mini App entry paths, Zalo launch URLs, QR payloads, and readiness state.
- `publish-distribution-flow`: Operator-facing publish workflow that generates launch artifacts, exposes them in admin, and keeps publish status separate from external channel readiness.
- `mini-app-entry-routing`: Runtime entry contract that opens the shared Mini App for a specific published game using a stable public identifier.

### Modified Capabilities
- None.

## Impact

- Affects Laravel publish logic, domain services, admin UI, and runtime URL shaping in `backend/`.
- Introduces new persistence for launch metadata and potentially QR asset references.
- Adds new admin behaviors around publish, copy/share, regenerate, and readiness validation.
- Shapes how the frontend Mini App resolves games from public entry identifiers rather than only from implicit local routes.
