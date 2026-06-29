## ADDED Requirements

### Requirement: Operators can manage campaign configuration
The system SHALL provide an authenticated admin interface where operators can create, update, activate, deactivate, and review campaign records without direct database access.

#### Scenario: Create a draft campaign
- **WHEN** an authenticated operator submits a new campaign with name, active period, and status
- **THEN** the system creates the campaign in draft or inactive state and stores it for later activation

#### Scenario: Prevent overlapping active campaigns when exclusivity is required
- **WHEN** an operator attempts to activate a campaign that conflicts with another active exclusive campaign
- **THEN** the system rejects the activation and explains the conflict

### Requirement: Operators can manage prize inventory and allocation settings
The system SHALL provide admin workflows to define prizes, prize types, allocation weights, quota limits, and availability state for each campaign.

#### Scenario: Configure a prize for a campaign
- **WHEN** an operator creates or updates a prize with campaign association, label, weight, and quota
- **THEN** the system stores the prize configuration and makes it available for server-side spin allocation

#### Scenario: Prevent invalid prize settings
- **WHEN** an operator submits a prize with invalid quota or missing campaign linkage
- **THEN** the system rejects the change and returns validation errors

### Requirement: Operators can manage reward codes
The system SHALL provide admin workflows to create, import, review, activate, and deactivate reward codes tied to campaigns.

#### Scenario: Import reward codes
- **WHEN** an operator uploads or pastes a batch of reward codes for a campaign
- **THEN** the system stores unique valid codes and reports duplicates or malformed entries

#### Scenario: Review reward code usage
- **WHEN** an operator opens the reward code listing
- **THEN** the system shows whether each code is unused, reserved, used, disabled, or otherwise unavailable

### Requirement: Operators can review player activity and claim state
The system SHALL provide admin views for players, spin results, and claims so operators can investigate campaign activity and fulfill rewards.

#### Scenario: Review a player history
- **WHEN** an operator opens a player record
- **THEN** the system shows submitted profile data, reward code usage, spin attempts, outcomes, and claim history for that player

#### Scenario: Mark a claim as fulfilled
- **WHEN** an operator updates a claim from pending to fulfilled
- **THEN** the system stores the new claim status and records who performed the action

### Requirement: Administrative actions are auditable
The system SHALL log security-sensitive and business-critical admin actions, including campaign changes, prize changes, reward code changes, and claim updates.

#### Scenario: Audit a prize configuration change
- **WHEN** an operator updates prize quota or allocation settings
- **THEN** the system stores an audit record containing the actor, timestamp, target object, and relevant before/after values

