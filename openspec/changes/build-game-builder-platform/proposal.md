## Why

The current project is a single-purpose Zalo mini app with one hardcoded lucky wheel experience. Colors, copy, form fields, prize inventory, gameplay rules, and redirect behavior all live in frontend code. That approach is workable for one campaign, but it cannot support a service where multiple customers register accounts, create their own branded games, collect player data, and manage outcomes through an operational backend.

The product goal has shifted from "ship one lucky wheel campaign" to "build a reusable game-builder platform." That means the system must support customer-owned game configuration, backend-controlled game logic, and future admin workflows without requiring source-code changes for each new launch.

## What Changes

- Build a PHP backend, using PostgreSQL and Docker, as the platform control plane for customer accounts, games, theme configuration, forms, prizes, gameplay rules, submissions, spins, claims, and integrations.
- Transform the mini app frontend into a runtime client that renders a game from backend configuration instead of hardcoded campaign content.
- Establish public runtime APIs for game bootstrap, form submission, eligibility checks, spin execution, claim handling, and redirect metadata.
- Prepare the domain and permissions model for a future admin experience where customers can register, create games, manage collected user data, and connect Zalo-specific destinations or flows.
- Separate platform concerns into customer management, game configuration, gameplay execution, and player data capture so the service can expand beyond a single campaign.

## Capabilities

### New Capabilities
- `game-builder-platform`: Multi-customer backend foundation for account ownership, game lifecycle, configuration storage, and operational data.
- `runtime-game-api`: Public APIs that power frontend-rendered games from server-side configuration and rules.
- `config-driven-mini-app`: Frontend runtime behavior that loads theme, copy, form, and prize definitions from backend APIs.

### Modified Capabilities
- `spin-engine-api`: Reframe from a single-campaign spin service into one runtime module of a broader game platform.
- `campaign-data-platform`: Expand from campaign storage into tenant-aware game configuration and player data storage.

## Impact

- Adds a new PHP backend that is designed for future admin UX, not just campaign APIs.
- Changes the frontend architecture from hardcoded presentation to config-driven rendering.
- Introduces customer/account concepts and tenant boundaries into the data model from the start.
- Enables future self-service onboarding, game creation, reporting, and Zalo linking without rebuilding the frontend per customer.
- Increases up-front modeling complexity, but avoids locking the product into a one-off campaign implementation.
