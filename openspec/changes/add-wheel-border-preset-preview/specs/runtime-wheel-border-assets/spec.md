## ADDED Requirements

### Requirement: Published wheel configuration includes runtime border asset metadata
The system SHALL publish wheel border metadata that allows the runtime mini app to render the same border asset family selected in the admin design step.

#### Scenario: Bootstrap returns the published border representation
- **WHEN** a published lucky wheel game is resolved through the bootstrap API
- **THEN** the response includes the published wheel border preset metadata
- **AND** the response provides enough information for the runtime client to resolve the matching border asset for display

#### Scenario: Published configuration preserves curated preset selection
- **WHEN** an operator saves a curated border preset and publishes the game
- **THEN** the published design configuration contains that preset as the fallback border choice
- **AND** later bootstrap responses continue to expose that published preset until a newer publish replaces it

### Requirement: Runtime wheel uses the published border asset consistently
The runtime mini app SHALL render the lucky wheel border from the published border asset metadata rather than from a disconnected approximation.

#### Scenario: Runtime wheel matches the published curated preset
- **WHEN** a player opens a published lucky wheel game with a curated border preset and no custom uploaded override
- **THEN** the runtime wheel renders the border using the asset associated with that published preset

#### Scenario: Runtime wheel respects uploaded border override
- **WHEN** a published lucky wheel game includes a custom uploaded outer-border image
- **THEN** the runtime wheel renders the uploaded border image instead of the curated preset asset
- **AND** the published curated preset remains available as the fallback configuration
