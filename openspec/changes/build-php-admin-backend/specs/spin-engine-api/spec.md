## ADDED Requirements

### Requirement: Runtime bootstrap reflects published builder configuration
The system SHALL expose runtime bootstrap data that reflects the currently published game-builder configuration rather than implicit frontend defaults or draft-only admin values.

#### Scenario: Load a published lucky wheel game
- **WHEN** the mini app requests bootstrap data for a published game
- **THEN** the system returns player-safe content, reward display data, and wheel-design configuration derived from the published builder state

### Requirement: Runtime availability follows admin publication controls
The system SHALL honor admin-managed publication and readiness state when deciding whether a game is available to the mini app.

#### Scenario: Draft changes do not affect the live game
- **WHEN** an operator saves builder changes without publishing them
- **THEN** the runtime API continues serving the last published configuration until a publish action succeeds

### Requirement: Runtime prize allocation uses admin-managed reward configuration
The system SHALL execute spins using the currently published reward configuration, including activation state, quota limits, and allocation weights managed in admin.

#### Scenario: Operator disables a prize before new spins
- **WHEN** an operator marks a prize as unavailable in admin and publishes the change
- **THEN** subsequent spin allocations exclude that prize from valid outcomes

### Requirement: Runtime claim and history records remain reviewable in admin
The system SHALL persist spin and claim outcomes in a form that can be surfaced through admin operational screens without requiring direct database access.

#### Scenario: Operator reviews a claim from runtime activity
- **WHEN** a player finishes a spin and claims a reward through the mobile flow
- **THEN** the resulting records are available for later inspection in the admin claim and activity views
