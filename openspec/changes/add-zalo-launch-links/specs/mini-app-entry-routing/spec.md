## ADDED Requirements

### Requirement: Shared Mini App routes resolve games by canonical public identifier
The runtime entry contract SHALL allow the shared Mini App to open a specific published game by passing the game's canonical public identifier through a path segment or query parameter.

#### Scenario: Mini App opens a game by public identifier
- **WHEN** the shared Mini App receives an entry request containing a valid canonical public identifier
- **THEN** the runtime resolves the corresponding published game
- **AND** the game bootstrap response is loaded for that game only

#### Scenario: Friendly slug is not required for canonical entry
- **WHEN** a game's slug changes after publication
- **THEN** the canonical public identifier still resolves the same game
- **AND** previously generated launch links based on that identifier remain valid

### Requirement: Runtime rejects unavailable launch entries safely
The runtime SHALL return an unavailable response when a launch entry targets a game that is missing, unpublished, or otherwise not accessible through public play.

#### Scenario: Public identifier does not exist
- **WHEN** the Mini App or launch URL requests a public identifier that is not mapped to any game
- **THEN** the runtime returns a game-unavailable response
- **AND** it does not expose unrelated game data

#### Scenario: Game is not publicly published
- **WHEN** the entry identifier belongs to a game whose runtime publication state is not active for public play
- **THEN** the runtime returns a game-unavailable response
- **AND** the shared Mini App does not continue into the play flow

### Requirement: Launch-link generation and runtime entry use the same identifier contract
The system SHALL keep launch-link generation aligned with runtime resolution so generated launch URLs always point to an identifier the runtime knows how to consume.

#### Scenario: Generated launch URL is opened
- **WHEN** a user opens a generated launch URL for a ready game
- **THEN** the identifier encoded in that URL maps to the same game in the runtime bootstrap flow
- **AND** the shared Mini App does not require manual identifier translation
