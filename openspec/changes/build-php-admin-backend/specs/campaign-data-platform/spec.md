## ADDED Requirements

### Requirement: Builder-managed game configuration is persisted in PostgreSQL
The system SHALL persist the game-builder configuration required by the admin, including theme, content, rewards, form schema, wheel-design settings, publication state, and related operational metadata, in PostgreSQL as the canonical platform database.

#### Scenario: Persist builder step changes
- **WHEN** the admin saves changes from any builder step
- **THEN** the system stores the updated configuration with relational integrity and makes it available for future editing and runtime consumption

### Requirement: Wheel-design metadata is modeled explicitly
The system SHALL persist wheel-design-specific metadata such as palette presets, border presets, pointer presets, asset references, and preview tokens as structured backend-owned configuration.

#### Scenario: Save a wheel design preset selection
- **WHEN** an operator chooses a wheel palette or border preset in the admin
- **THEN** the system stores the selected preset in a structured form that can be reused by both preview and runtime rendering

### Requirement: Operational records remain attributable to a game and workspace
The system SHALL persist reward codes, submissions, spin records, claims, and related operator actions with enough linkage to support game-level and workspace-level investigation.

#### Scenario: Investigate a disputed reward outcome
- **WHEN** an operator reviews a submission, spin result, or claim in admin
- **THEN** the system provides persisted records that can be traced back to the owning game, workspace, and operator actions

### Requirement: Publication state is stored separately from draft editing progress
The system SHALL support persisted draft editing and publication state so operators can continue configuring a game without immediately changing the live runtime experience.

#### Scenario: Save draft changes without publishing
- **WHEN** an operator saves builder changes for a game that is not yet ready to go live
- **THEN** the system preserves the draft configuration while keeping the current published runtime state unchanged until an explicit publish action occurs
