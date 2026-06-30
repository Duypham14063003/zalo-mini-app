## ADDED Requirements

### Requirement: Publishing generates distribution artifacts
The publish workflow SHALL generate or refresh launch artifacts for supported distribution channels after a game has been successfully published into runtime state.

#### Scenario: Publish succeeds and launch artifacts are generated
- **WHEN** an operator publishes a valid game from the admin builder
- **THEN** the system publishes the runtime configuration
- **AND** it generates or refreshes launch metadata for the configured launch channels

#### Scenario: Launch generation does not silently skip
- **WHEN** publish succeeds
- **THEN** the system records whether launch generation completed successfully for each expected channel
- **AND** the operator receives feedback about the launch outcome

### Requirement: Admin shows deployment-ready outputs after publish
The admin experience SHALL expose the latest launch outputs for a published game, including the canonical public identifier, launch links, QR payload, and channel readiness.

#### Scenario: Operator views launch outputs after publish
- **WHEN** an operator returns to a published game's edit page
- **THEN** the admin shows the current launch metadata for the game
- **AND** the view distinguishes between preview/runtime links and Zalo-facing launch links

#### Scenario: Operator copies a deployment link
- **WHEN** an operator chooses a generated launch link from the admin surface
- **THEN** the system presents a copyable value for that channel
- **AND** the operator does not need to reconstruct the link manually

### Requirement: Operators can regenerate launch artifacts
The admin SHALL allow operators to regenerate launch metadata without requiring them to create a new game or change its canonical identifier unless the identifier itself has changed.

#### Scenario: Operator regenerates a Zalo launch link
- **WHEN** an operator triggers launch regeneration for a published game
- **THEN** the system recalculates the launch artifacts for the selected channel
- **AND** the stored launch metadata is updated in place

#### Scenario: Regeneration preserves canonical identifier
- **WHEN** launch artifacts are regenerated for a game whose primary public identifier has not changed
- **THEN** the same canonical public identifier remains attached to the launch record
- **AND** downstream references to that game do not require a new identifier

### Requirement: Admin warns when launch is not externally ready
The publish surface SHALL make non-ready launch states explicit so operators do not mistake internal publication for customer-ready Zalo distribution.

#### Scenario: Launch record is not ready
- **WHEN** a game's launch record has a non-ready status
- **THEN** the admin highlights that the game is not yet ready for external distribution
- **AND** the operator can still see the internal publication state separately
