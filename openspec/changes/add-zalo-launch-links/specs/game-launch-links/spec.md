## ADDED Requirements

### Requirement: Published games have persisted launch records
The system SHALL persist launch metadata for each published game in a dedicated launch-link store that is separate from the base game record and separate from builder draft configuration.

#### Scenario: Launch record is created for a newly published game
- **WHEN** an operator publishes a game that does not yet have a launch record for the `zalo_mini_app` channel
- **THEN** the system creates a launch record linked to that game and workspace
- **AND** the launch record stores the canonical public identifier used for entry

#### Scenario: Launch record is refreshed for an already published game
- **WHEN** an operator republishes a game that already has a launch record for the `zalo_mini_app` channel
- **THEN** the system updates the existing launch record instead of creating a duplicate
- **AND** the generated launch fields reflect the latest published identifier and route data

### Requirement: Launch records store channel-specific entry artifacts
Each launch record SHALL store enough information to support distribution and troubleshooting for one channel, including the public identifier, Mini App path, launch URL, QR payload, readiness status, and generation timestamps.

#### Scenario: Admin inspects a generated launch record
- **WHEN** the system loads launch metadata for a published game
- **THEN** it returns the channel, public identifier, Mini App path, launch URL, QR payload, and status
- **AND** it includes when the launch metadata was generated

#### Scenario: QR payload exists without stored image asset
- **WHEN** the system generates launch metadata in an environment where QR images are not persisted
- **THEN** the launch record still stores a QR payload value
- **AND** the absence of a QR asset path does not invalidate the record by itself

### Requirement: Launch readiness is tracked independently from publish state
The system SHALL track whether a generated launch record is externally ready without changing the meaning of the game's internal published state.

#### Scenario: Game is published but Zalo launch template is unavailable
- **WHEN** runtime publication succeeds but the system cannot produce a production-ready Zalo launch URL
- **THEN** the game remains published for runtime use
- **AND** the launch record is marked with a non-ready status

#### Scenario: Launch record becomes ready
- **WHEN** the system successfully generates all required channel fields for a Zalo Mini App launch
- **THEN** the launch record status is set to `ready`
- **AND** operators can distinguish that state from the game's internal publication timestamp
