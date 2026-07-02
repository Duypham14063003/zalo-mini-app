## ADDED Requirements

### Requirement: Workspace owners can manage reusable theme assets
The system SHALL provide a workspace-scoped theme asset library where a workspace owner can create, update, activate, deactivate, sort, and delete reusable visual assets for supported lucky wheel design slots.

#### Scenario: Owner creates a background preset
- **WHEN** a workspace owner uploads a new asset with type `background`, a display name, and an active status
- **THEN** the system stores the asset under the workspace library
- **AND** associates it with the owner-managed workspace
- **AND** makes it available to game editors in that workspace

#### Scenario: Owner uploads multiple slot types
- **WHEN** a workspace owner adds assets for `background`, `banner`, `spin_button`, `extra_spin_button`, `wheel_border`, and `wheel_pointer`
- **THEN** the system stores each asset with its slot type
- **AND** only exposes each asset as an option for matching design slots

#### Scenario: Inactive assets are hidden from game editors
- **WHEN** a workspace owner marks a workspace theme asset as inactive
- **THEN** the asset remains stored in the library
- **AND** the asset is no longer shown as a selectable preset in game design forms

### Requirement: Theme asset types enforce expected media rules
The system SHALL enforce asset-type-specific validation for workspace theme assets so unsupported file formats cannot be used for a slot.

#### Scenario: Wheel pointer accepts PNG assets only
- **WHEN** a workspace owner uploads a wheel pointer preset
- **THEN** the system accepts PNG image files
- **AND** rejects non-PNG files for that asset type

#### Scenario: Banner uses a static image asset
- **WHEN** a workspace owner uploads a banner preset
- **THEN** the system stores it as a static image asset
- **AND** does not require any text overlay metadata for the banner slot

### Requirement: Workspace asset libraries are isolated by workspace
The system SHALL expose only the workspace asset library owned by or shared with the current workspace context.

#### Scenario: Editor sees only presets from their workspace
- **WHEN** a non-admin user opens a game design form for a workspace game
- **THEN** the preset options come only from the current workspace asset library
- **AND** the user does not see presets from another workspace
