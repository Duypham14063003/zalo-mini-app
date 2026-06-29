## Why

The current mini app is frontend-only: form validation, campaign stats, spin results, and reward claiming are all handled locally in the client. This makes the campaign impossible to operate safely in production because reward codes, prize quotas, spin outcomes, and claim records cannot be verified or managed centrally.

## What Changes

- Build a PHP backend, using PostgreSQL in Docker, to become the source of truth for campaign data, players, reward codes, spin attempts, prize allocation, and claim records.
- Add mobile-facing APIs for campaign bootstrap, eligibility checks, server-side spin execution, reward claiming, and player history.
- Add an admin web interface for operators to manage campaigns, prizes, reward codes, players, spin results, and claims.
- Add audit-friendly operational workflows so campaign staff can review quota usage, claim status, and player activity without touching the database directly.
- Define Docker-based local infrastructure for the PHP app and PostgreSQL so development and deployment setup are reproducible.

## Capabilities

### New Capabilities
- `campaign-admin`: Admin console for managing campaigns, prizes, reward codes, players, spin results, and claim workflows.
- `spin-engine-api`: Mobile API for campaign bootstrap, eligibility validation, server-side spin execution, reward claiming, and player history.
- `campaign-data-platform`: PostgreSQL-backed domain model, operational rules, and Docker runtime for persistent campaign data and reliable local setup.

### Modified Capabilities
- None.

## Impact

- Adds a new PHP backend application and admin interface.
- Introduces PostgreSQL as the primary persistence layer, running in Docker for local development.
- Moves spin outcome selection and reward-claim state from frontend-only logic into backend-controlled transactions.
- Requires the mini app frontend to replace hardcoded stats, local random spin logic, and mock claim flow with API integration in a later implementation phase.
