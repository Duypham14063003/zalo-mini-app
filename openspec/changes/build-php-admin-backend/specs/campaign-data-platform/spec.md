## ADDED Requirements

### Requirement: Campaign data is persisted in PostgreSQL
The system SHALL persist campaigns, prizes, reward codes, players, spins, results, claims, admin users, and audit records in PostgreSQL as the canonical campaign database.

#### Scenario: Persist campaign domain records
- **WHEN** the backend creates or updates campaign domain entities
- **THEN** the system stores them in PostgreSQL with relational integrity appropriate to the domain model

### Requirement: Local development uses Docker-managed infrastructure
The system SHALL provide Docker-based local infrastructure for the PHP application runtime and PostgreSQL database so developers can run the backend consistently.

#### Scenario: Start local infrastructure
- **WHEN** a developer starts the local development stack
- **THEN** the system launches the required application and PostgreSQL services using Docker-defined configuration

### Requirement: Prize allocation and claims are transaction-safe
The system SHALL protect prize allocation and claim state changes with transactional guarantees that prevent inconsistent quota usage and duplicate state transitions.

#### Scenario: Concurrent spin requests target limited inventory
- **WHEN** multiple eligible spin requests arrive for a prize set with limited quota
- **THEN** the system preserves quota correctness and does not over-allocate prizes

#### Scenario: Claim processing retries after transient failure
- **WHEN** claim processing is retried after a partial or transient failure
- **THEN** the system preserves a consistent claim state without duplicating the reward transition

### Requirement: The platform supports operational observability
The system SHALL store sufficient operational records to inspect campaign execution, including actor attribution, timestamps, and state transitions for sensitive actions.

#### Scenario: Investigate an unexpected prize outcome
- **WHEN** an operator or developer investigates a disputed spin or claim
- **THEN** the system provides enough persisted records to trace the relevant eligibility, spin, allocation, and claim events
