## MODIFIED Requirements

### Requirement: Spin execution supports template-driven public games
The system SHALL execute lucky wheel spins as one runtime service within a broader game platform, resolving the target public game, validating workspace-owned rules, and persisting outcomes under the owning workspace.

#### Scenario: Spin a public lucky wheel game
- **WHEN** the runtime receives a spin request for a public lucky wheel game
- **THEN** the system resolves the game, validates that it is active for the owning workspace, executes the spin under that game's rule set, and persists the outcome under the same workspace

#### Scenario: Reject a spin for the wrong game context
- **WHEN** a player submission or spin request is replayed against a different public game identity
- **THEN** the system rejects the request and does not leak or reuse gameplay state across games

### Requirement: Claim transitions support configurable post-claim actions
The system SHALL process claims idempotently and return the configured next action for the public game, including close-app behavior, Zalo OA routing, or fallback destinations.

#### Scenario: Claim a reward and return OA routing
- **WHEN** a player claims a reward for a game configured to open a Zalo Official Account
- **THEN** the system stores the claim transition and returns the OA routing metadata required by the frontend
