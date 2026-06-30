## ADDED Requirements

### Requirement: Customer workspaces own isolated game data
The system SHALL assign each game, configuration record, player submission, spin result, claim, and integration record to a specific customer workspace so that customers only access their own data.

#### Scenario: Create a game inside a workspace
- **WHEN** an authenticated workspace owner creates a new game
- **THEN** the system stores the game under that workspace and isolates it from every other workspace

#### Scenario: Resolve a public game to its owner workspace
- **WHEN** the runtime system receives a public game identifier from a QR code or public link
- **THEN** the system resolves the matching game and its owning workspace before loading any runtime configuration

### Requirement: The platform supports reusable game templates
The system SHALL model each game as a template-driven record so that the current lucky wheel is the first supported template and additional game types can be introduced later without redesigning the ownership model.

#### Scenario: Create a lucky wheel game
- **WHEN** a workspace owner creates a game with template type `lucky_wheel`
- **THEN** the system stores the game template type and provisions the configuration sections required by the lucky wheel runtime

### Requirement: Workspace members can configure game presentation
The system SHALL persist game-specific theme, copy, form schema, gameplay rules, and redirect behavior as configuration data instead of hardcoded frontend values.

#### Scenario: Update game branding
- **WHEN** a workspace member updates a game's primary color, headings, or CTA labels
- **THEN** the system stores the configuration changes and makes them available to the runtime bootstrap API

#### Scenario: Configure a custom form field
- **WHEN** a workspace member adds a required select field with predefined options
- **THEN** the system stores the field definition with type, label, requirement state, options, and display order

### Requirement: Platform operators have full cross-workspace access
The system SHALL support a platform-level administrator role that can inspect and manage every workspace for support, operations, and debugging.

#### Scenario: Platform administrator reviews a customer game
- **WHEN** a platform administrator opens a game owned by another workspace
- **THEN** the system allows access and records the privileged action for operational traceability
