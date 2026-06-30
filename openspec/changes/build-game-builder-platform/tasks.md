## 1. Platform Foundation

- [x] 1.1 Scaffold a Laravel backend application for the shared Zalo mini app platform.
- [x] 1.2 Add Docker Compose services for the PHP runtime, web server, PostgreSQL, and any supporting local infrastructure required by the platform.
- [x] 1.3 Configure environment variables, local bootstrap documentation, and baseline authentication for platform operators.
- [x] 1.4 Define the initial authorization model for platform administrators, workspace owners, and future workspace staff roles.

## 2. Ownership And Configuration Domain

- [x] 2.1 Create migrations for accounts, workspaces, workspace users or memberships, games, and public game identifiers.
- [x] 2.2 Create migrations for game themes, content blocks, form fields, game rules, redirects, prizes, reward codes, players, player submissions, spin attempts, spin results, claims, integrations, and audit logs.
- [x] 2.3 Implement Eloquent models, relationships, enums, and validation rules for the platform ownership and lucky wheel configuration domain.
- [x] 2.4 Seed the database with a platform administrator, a sample workspace, and a sample lucky wheel game configuration for local testing.

## 3. Runtime Gameplay APIs

- [x] 3.1 Implement the public bootstrap endpoint that resolves a game from its QR-safe public identity and returns player-safe runtime configuration.
- [x] 3.2 Implement the player submission endpoint that validates dynamic form schemas and stores player submission records.
- [x] 3.3 Implement eligibility-check and reward-code validation behavior for the lucky wheel template.
- [x] 3.4 Implement the server-authoritative spin endpoint with transactional prize allocation and workspace-safe persistence.
- [x] 3.5 Implement the claim endpoint with idempotent reward claiming and configurable post-claim routing metadata, including Zalo OA support.

## 4. Config-Driven Mini App Runtime

- [x] 4.1 Refactor the frontend to load bootstrap configuration from the backend instead of using hardcoded campaign values.
- [x] 4.2 Refactor the player form to render dynamically from backend-provided field definitions and submit to the new submission API.
- [x] 4.3 Update the spin flow so the frontend animates backend-awarded results instead of choosing a local random winner.
- [x] 4.4 Update the claim flow so the frontend executes backend-provided post-claim actions such as close-app or redirect behavior.

## 5. Management Readiness And Hardening

- [x] 5.1 Add audit logging and actor attribution for platform-level and workspace-level configuration changes.
- [x] 5.2 Add route protection, workspace scoping, and privileged platform-admin access rules for future management surfaces.
- [x] 5.3 Add automated tests for workspace isolation, bootstrap resolution, dynamic form validation, spin allocation, claim idempotency, and QR-safe public identity handling.
- [x] 5.4 Verify the Dockerized stack, migration flow, seeded sample workspace, and end-to-end shared-app runtime path for at least one sample customer game.
