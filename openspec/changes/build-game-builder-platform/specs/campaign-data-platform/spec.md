## MODIFIED Requirements

### Requirement: The data platform stores customer-owned game configuration and runtime data
The system SHALL persist workspace ownership, public game identity, template configuration, prizes, reward codes, players, submissions, spins, claims, integrations, and audit records in PostgreSQL as the canonical platform database.

#### Scenario: Persist a game and its runtime configuration
- **WHEN** the backend creates or updates a game owned by a workspace
- **THEN** the system stores the game and its related theme, content, form, rule, and redirect configuration with relational integrity

#### Scenario: Persist player runtime activity
- **WHEN** a player submits a form, spins, or claims a reward
- **THEN** the system stores those records under the owning game and workspace so they can be queried safely later

### Requirement: Public game identifiers are resolvable and safe for runtime use
The system SHALL provide a public game identity model that can be embedded in QR codes or links without exposing privileged admin credentials.

#### Scenario: Load a game from a QR code slug
- **WHEN** the runtime receives a public slug or public identifier from a QR-driven request
- **THEN** the system resolves the game and workspace from that public identity without requiring an admin login

### Requirement: The platform supports future management surfaces
The system SHALL store enough ownership, role, and audit structure to support future admin experiences for both platform operators and customer workspace users.

#### Scenario: Attribute a configuration change
- **WHEN** an authenticated user updates a game configuration record
- **THEN** the system stores actor attribution and timestamps so future admin tooling can display and audit the change
