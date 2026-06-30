## ADDED Requirements

### Requirement: The mini app renders from backend configuration
The mini app SHALL initialize game presentation from backend-provided bootstrap data rather than from hardcoded campaign values embedded in source code.

#### Scenario: Render theme and content from bootstrap
- **WHEN** the frontend receives a valid game bootstrap response
- **THEN** it renders the game's configured title, subtitle, colors, labels, and prize display data from the response

### Requirement: The mini app supports configurable player forms
The mini app SHALL render form fields dynamically from the backend-provided form schema for the current game template.

#### Scenario: Render configured required fields
- **WHEN** the bootstrap response includes required text and select fields
- **THEN** the frontend renders the configured inputs in order and blocks submission until required fields are satisfied

### Requirement: The mini app delegates business decisions to the backend
The mini app SHALL treat the backend as authoritative for eligibility, spin outcomes, claim status, and post-claim routing.

#### Scenario: Animate a backend-awarded prize
- **WHEN** the backend returns the winning prize for a spin request
- **THEN** the frontend animates the wheel to the corresponding configured segment instead of generating its own random winner

#### Scenario: Apply backend claim routing
- **WHEN** the backend returns a post-claim action after reward acceptance
- **THEN** the frontend executes the configured close, redirect, or follow-up behavior
