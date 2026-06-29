## Context

The current mini app is a single frontend flow with no persistent backend. Form submission, loading state, spin result selection, campaign statistics, and reward claiming are all handled locally in the browser. For a real campaign, this is insufficient because reward codes can be replayed, prize quotas cannot be enforced, operators have no management interface, and there is no audit trail for claims or prize distribution.

The requested direction is a PHP-based backend with PostgreSQL, while the database runtime should be managed through Docker. The system must support two primary clients:

- the Zalo mini app, which needs low-latency APIs for campaign bootstrap, eligibility checks, spinning, and claiming
- internal operators, who need an admin interface to manage campaign setup and daily operations

This is a cross-cutting change because it introduces a new application tier, persistent data model, operational UI, and local infrastructure.

## Goals / Non-Goals

**Goals:**
- Establish a Laravel-based backend as the source of truth for campaigns, reward codes, spins, prizes, and claims.
- Provide mobile APIs that move all prize allocation and claim state transitions to the server.
- Provide an admin console for campaign operators to manage campaigns, prizes, reward codes, players, and claim workflows.
- Use PostgreSQL in Docker for local development and consistent deployment setup.
- Preserve a clean boundary between public mobile APIs and authenticated operator-only admin functions.

**Non-Goals:**
- Rebuilding the mini app frontend as part of this change.
- Designing advanced BI/reporting exports beyond operational dashboards and list views.
- Supporting multiple campaign brands or tenants in the first release.
- Implementing external fulfillment providers in the first release unless a prize type explicitly requires it later.

## Decisions

### 1. Use Laravel as the PHP application framework

Laravel is the best fit because the scope includes REST APIs, admin authentication, validation, migrations, queues, policies, and a server-rendered admin portal. It reduces setup overhead and gives a reliable path for both operator tooling and mobile integration.

Alternatives considered:
- Plain PHP or a micro-framework: lower framework overhead, but too much infrastructure would need to be built manually.
- Symfony: technically strong, but slower for this CRUD-heavy admin and API use case unless the team already standardizes on it.

### 2. Use PostgreSQL as the system of record

PostgreSQL is a strong fit for transactional campaign data, relational integrity, audit history, and future analytical queries. It also handles concurrency patterns well for prize quota enforcement and claim state transitions.

Alternatives considered:
- MySQL: viable, but the user explicitly chose PostgreSQL.
- SQLite: not suitable for concurrent, operational campaign workloads.

### 3. Run local infrastructure with Docker Compose

The backend should include Docker Compose services for at least:
- `app` or PHP runtime
- `web` or Nginx
- `postgres`

An optional `redis` service should be included if rate limiting, queue workers, or distributed locks are needed during implementation. Docker Compose gives reproducible local setup and reduces environment drift.

Alternatives considered:
- Native host installation: faster initially for one developer, but much harder to standardize and onboard.

### 4. Split the platform into public API and authenticated admin surfaces

The system should expose:
- a versioned mobile API namespace for the mini app
- an operator-only admin web surface guarded by Laravel authentication and authorization

This separation reduces accidental privilege leakage and allows different rate limiting, validation, and observability for operator flows versus public campaign traffic.

Alternatives considered:
- One unified web app with mixed pages and endpoints: simpler initially, but weaker separation of concerns and more error-prone access control.

### 5. Make the backend authoritative for spin outcomes and claim transitions

Spin results must never be decided by frontend randomness. The backend should validate eligibility, check campaign state, allocate prizes according to configured rules and quota constraints, and persist the result atomically. Claims should also be modeled as explicit state transitions so duplicate submissions are safe.

Alternatives considered:
- Frontend random with backend logging: simpler to prototype, but not acceptable for fraud prevention or inventory control.

### 6. Model the initial domain around campaigns, reward codes, players, spins, prizes, and claims

The first release should center on these entities:
- campaigns
- prizes
- reward_codes
- players
- spin_attempts or spin_sessions
- spin_results
- claims
- admins
- audit_logs

This model cleanly supports the current mobile flow and the expected admin operations without prematurely generalizing the system.

### 7. Start the admin with server-rendered CRUD flows

The admin interface should begin as a server-rendered Laravel application rather than a separate SPA. This keeps the operational interface faster to build and easier to maintain during the MVP phase.

Alternatives considered:
- Separate SPA admin: better long-term UX flexibility, but adds another frontend stack before the campaign engine is stable.

## Risks / Trade-offs

- [Backend and admin are both in scope] → Mitigation: deliver the first release in layers: foundation, admin CRUD, mobile APIs, then operational hardening.
- [Prize quota oversubscription under concurrent traffic] → Mitigation: use database transactions, row-level locking, and explicit quota counters or allocation records.
- [Campaign rules may still evolve while implementation begins] → Mitigation: keep rules configurable at the campaign and prize layers instead of hardcoding them in the mini app.
- [The mini app currently has hardcoded text and local flow assumptions] → Mitigation: define bootstrap APIs early so frontend integration can progressively replace local state.
- [Operator workflows may expand after launch] → Mitigation: capture audit logs and normalized state transitions from day one so later screens can build on reliable data.

## Migration Plan

1. Scaffold the Laravel application and Docker Compose services for PHP, web server, and PostgreSQL.
2. Create migrations and seeders for the initial campaign domain model.
3. Implement admin authentication and baseline CRUD for campaigns, prizes, and reward codes.
4. Implement mobile bootstrap and eligibility APIs.
5. Implement transactional spin and claim flows.
6. Integrate the mini app with the new APIs in a separate implementation phase.
7. Roll out to staging with sample campaign data, then production with operator validation.

Rollback strategy:
- Keep the current mini app frontend in fallback/mock mode until the backend endpoints are validated in staging.
- Deploy database migrations in additive steps where possible.
- If rollout fails, disable the campaign in admin and point the frontend back to non-live behavior until backend fixes are applied.

## Open Questions

- Will reward codes be one-time-use only, or can a code grant multiple spins?
- Does each claim require manual operator confirmation, or can some prize types auto-fulfill?
- Is Zalo user identity required in the first release, or is phone number plus reward code sufficient for MVP?
- Do operators need CSV import/export for reward codes and claim history in the first milestone, or can that wait until after launch?
