## ADDED Requirements

### Requirement: Operators use a builder-oriented admin shell
The system SHALL provide an authenticated admin shell that presents a product-style navigation layout with a left navigation rail, top toolbar, and workspace-focused content area for campaign operations.

#### Scenario: Operator enters the admin workspace
- **WHEN** an authenticated operator visits the admin area
- **THEN** the system renders the product shell with navigation, account context, and access to game-building workflows instead of a generic dashboard-only layout

### Requirement: Operators edit a lucky wheel game through a guided multi-step builder
The system SHALL provide a step-based game editing workflow for general settings, reward configuration, wheel design, and final game presentation.

#### Scenario: Operator moves between builder steps
- **WHEN** an operator opens a game builder and navigates between steps
- **THEN** the system preserves the current game context and presents step-specific controls with clear progression actions such as save, back, and continue

### Requirement: Operators can preview wheel and presentation changes before publishing
The system SHALL provide a live or near-real-time preview panel that reflects the currently edited game configuration during the builder workflow.

#### Scenario: Operator updates wheel presentation values
- **WHEN** an operator changes theme values, reward labels, or wheel design settings in the builder
- **THEN** the system updates the preview so the operator can validate the resulting game appearance before publishing

### Requirement: Operators can manage wheel design presets and assets
The system SHALL provide admin controls for wheel-specific design selections such as color palettes, border presets, pointer presets, and related visual assets required by the lucky wheel template.

#### Scenario: Operator selects a wheel border preset
- **WHEN** an operator chooses a predefined wheel border or pointer style in the builder
- **THEN** the system stores that choice and shows the selected design in preview and future runtime bootstrap output

### Requirement: Operators can complete operational management from admin
The system SHALL provide operator workflows for reward codes, submissions, spin histories, claim records, and publish-state actions without requiring database access.

#### Scenario: Operator reviews operational records for a game
- **WHEN** an operator opens the management area for a game
- **THEN** the system provides navigable views for reward codes, player submissions, spin results, and claim states associated with that game

### Requirement: Operators can control game publication readiness
The system SHALL provide explicit publish-state controls and validation-aware review before a game becomes the live runtime experience.

#### Scenario: Operator attempts to publish an incomplete game
- **WHEN** an operator tries to publish a game with missing required builder configuration
- **THEN** the system blocks publication and identifies the missing configuration that must be completed
