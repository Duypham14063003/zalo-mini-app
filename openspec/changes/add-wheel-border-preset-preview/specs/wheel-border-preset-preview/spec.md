## ADDED Requirements

### Requirement: Admin can select a wheel border from the curated border asset list
The system SHALL present the curated lucky wheel border presets as a visual selectable list in the lucky wheel design step, using the configured border assets rather than text-only options.

#### Scenario: Border preset list is shown to the operator
- **WHEN** an operator opens the lucky wheel design step in the admin builder
- **THEN** the system displays each curated border preset as a distinct visual option
- **AND** each option includes a stable preset identifier behind the selection
- **AND** the preset list is derived from the curated border asset set configured for the project

#### Scenario: Operator chooses a curated border preset
- **WHEN** the operator selects one border preset from the visual list
- **THEN** the form state stores the selected preset id as the current wheel border preset
- **AND** the selected option is visibly marked as active
- **AND** any later save or publish action uses that preset id as the chosen border preset

### Requirement: Admin preview reflects the selected wheel border preset immediately
The system SHALL update the lucky wheel preview in the admin design step when the selected border preset changes.

#### Scenario: Preview updates after preset selection
- **WHEN** the operator changes the selected wheel border preset
- **THEN** the admin wheel preview redraws using the newly selected curated border asset
- **AND** the preview update happens before publish

#### Scenario: Custom upload remains the higher-priority preview source
- **WHEN** the operator has both a curated border preset selected and a custom uploaded outer-border image configured
- **THEN** the admin preview uses the custom uploaded image as the rendered border
- **AND** the UI preserves the curated preset selection as the fallback border choice
