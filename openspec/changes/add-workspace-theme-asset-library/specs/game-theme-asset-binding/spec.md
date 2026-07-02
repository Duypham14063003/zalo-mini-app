## ADDED Requirements

### Requirement: Game editors can choose preset assets per design slot
The system SHALL let a game editor bind each supported theme slot to a workspace preset asset during game configuration.

#### Scenario: Editor selects a preset for a slot
- **WHEN** a game editor selects a workspace preset for a supported slot
- **THEN** the game draft stores the selected preset identifier for that slot
- **AND** the preview updates to show the selected preset

#### Scenario: Supported slots are presented separately
- **WHEN** a game editor configures the visual design of a lucky wheel game
- **THEN** the system presents distinct controls for `background`, `banner`, `spin_button`, `extra_spin_button`, `wheel_border`, and `wheel_pointer`
- **AND** each control lists only matching presets for its slot

### Requirement: Game editors can upload per-game overrides
The system SHALL allow a game editor to upload a per-game override image for each supported theme slot, and that override SHALL take precedence over the selected preset.

#### Scenario: Override replaces preset in preview
- **WHEN** a game editor selects a preset and then uploads a per-game override for the same slot
- **THEN** the preview uses the uploaded override
- **AND** the selected preset remains stored as the fallback choice

#### Scenario: Removing an override restores the preset
- **WHEN** a game editor removes a per-game override image for a slot that still has a selected preset
- **THEN** the preview falls back to the selected workspace preset

### Requirement: Published runtime theme assets resolve consistently
The system SHALL publish runtime-ready asset metadata so the mini app renders the same assets chosen in the admin builder.

#### Scenario: Bootstrap returns resolved theme assets
- **WHEN** a published game is loaded through the bootstrap API
- **THEN** the response includes resolved asset metadata for background, banner, spin button, extra-spin button, wheel border, and wheel pointer
- **AND** each slot prefers the per-game override when present
- **AND** otherwise falls back to the selected workspace preset

### Requirement: Extra spin button appears only when the player has no spins left
The runtime SHALL show the configured extra-spin button only when the current player has no remaining spins.

#### Scenario: Extra spin button stays hidden while spins remain
- **WHEN** the current player still has one or more remaining spins
- **THEN** the runtime does not render the extra-spin button state

#### Scenario: Extra spin button appears after spins run out
- **WHEN** the current player reaches zero remaining spins
- **THEN** the runtime renders the configured extra-spin button asset
- **AND** places it in the post-spin flow where the player can take the next configured action
