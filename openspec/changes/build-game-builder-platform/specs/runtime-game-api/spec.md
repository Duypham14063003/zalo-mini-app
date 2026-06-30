## ADDED Requirements

### Requirement: The runtime can bootstrap a public game from a QR-resolved identity
The system SHALL expose a public bootstrap API that accepts a public game identity and returns the active runtime configuration needed to render a game safely on the client.

#### Scenario: Load an active game
- **WHEN** the mini app requests bootstrap data for an active public game
- **THEN** the system returns player-safe theme, copy, form schema, prize display data, runtime rule hints, and redirect metadata for that game

#### Scenario: Reject an unavailable game
- **WHEN** the mini app requests bootstrap data for a missing, inactive, unpublished, or expired public game
- **THEN** the system returns a response that explicitly marks the game as unavailable

### Requirement: The runtime can capture player submissions
The system SHALL expose an API that validates and stores player-submitted form data for a specific game before gameplay continues.

#### Scenario: Submit valid player data
- **WHEN** the mini app submits a valid payload matching the configured form schema
- **THEN** the system stores a player submission linked to the game and returns a player context for follow-up runtime actions

#### Scenario: Reject invalid form data
- **WHEN** the mini app submits a payload that violates required fields, data types, or allowed options
- **THEN** the system rejects the submission with field-level validation errors

### Requirement: The runtime can execute server-authoritative gameplay
The system SHALL expose APIs that validate eligibility, execute a spin, enforce prize rules, and persist outcomes for the current lucky wheel template.

#### Scenario: Spin a valid player session
- **WHEN** an eligible player requests a spin for an active lucky wheel game
- **THEN** the system evaluates game rules, allocates a valid outcome, persists the result, and returns the awarded prize payload needed for client animation and reward display

#### Scenario: Block an invalid spin attempt
- **WHEN** the player is ineligible because of submission state, exhausted attempts, disabled reward code, or unavailable game state
- **THEN** the system rejects the spin request with a clear non-eligible response

### Requirement: The runtime can finalize reward claims and post-claim routing
The system SHALL expose a claim API that records reward acceptance and returns the configured next action such as closing the app or redirecting to a linked Zalo destination.

#### Scenario: Claim a reward with redirect instructions
- **WHEN** a player successfully claims a valid spin result
- **THEN** the system stores the claim state and returns the configured post-claim action and target metadata

#### Scenario: Retry an already claimed reward
- **WHEN** a player repeats a claim request for an already claimed result
- **THEN** the system responds idempotently without creating a duplicate claim
