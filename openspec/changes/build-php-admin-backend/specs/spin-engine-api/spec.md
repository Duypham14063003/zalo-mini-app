## ADDED Requirements

### Requirement: The mini app can bootstrap current campaign data
The system SHALL expose a mobile API that returns the active campaign metadata needed to render the mini app experience, including campaign copy, prize listing, and summary statistics approved for player display.

#### Scenario: Load the active campaign
- **WHEN** the mini app requests current campaign data during startup
- **THEN** the system returns the active campaign configuration and player-safe display data for the current experience

#### Scenario: No active campaign is available
- **WHEN** the mini app requests current campaign data and no campaign is active
- **THEN** the system returns a response that explicitly indicates the campaign is unavailable

### Requirement: The mini app can validate eligibility before spinning
The system SHALL expose an API that validates submitted player information and reward code state before allowing a spin attempt.

#### Scenario: Eligible player proceeds to spin
- **WHEN** the mini app submits a valid reward code and required player information for an active campaign
- **THEN** the system confirms eligibility and returns the player's available spin entitlement

#### Scenario: Invalid or exhausted reward code is rejected
- **WHEN** the mini app submits a reward code that is invalid, disabled, expired, or already consumed
- **THEN** the system rejects the request and returns a non-eligible response with a clear reason

### Requirement: Spin outcomes are allocated by the server
The system SHALL expose an API that executes a spin attempt on the server, applies campaign rules, enforces prize availability, and persists the result atomically.

#### Scenario: Successful spin allocation
- **WHEN** an eligible player submits a spin request
- **THEN** the system allocates a valid outcome, stores the spin result, updates relevant counters, and returns the awarded prize

#### Scenario: Prize exhaustion affects allocation
- **WHEN** a candidate prize is unavailable due to quota exhaustion or disabled status
- **THEN** the system excludes that prize from allocation and returns only an outcome that remains valid under campaign rules

### Requirement: Reward claiming is handled as a backend state transition
The system SHALL expose an API that records reward claim attempts and safely transitions eligible spin results into claimed state.

#### Scenario: Claim an unclaimed reward
- **WHEN** the player claims a reward tied to a valid unclaimed spin result
- **THEN** the system marks the reward as claimed and returns the updated claim state

#### Scenario: Duplicate claim submission is retried
- **WHEN** the player repeats a claim request for the same already claimed result
- **THEN** the system responds idempotently without creating duplicate claims

### Requirement: Players can retrieve their spin history
The system SHALL expose an API that returns the authenticated or identified player's campaign participation history.

#### Scenario: Load prior spin results
- **WHEN** the mini app requests player history for a valid player context
- **THEN** the system returns the player's prior spins, outcomes, and claim states

