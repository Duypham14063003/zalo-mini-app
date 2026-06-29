## 1. Platform Foundation

- [x] 1.1 Scaffold a Laravel backend application for the project.
- [x] 1.2 Add Docker Compose services for the PHP runtime, web server, and PostgreSQL.
- [x] 1.3 Configure environment variables, database connectivity, and local bootstrap documentation for Docker-based development.
- [x] 1.4 Set up baseline Laravel authentication for admin users.

## 2. Campaign Domain Model

- [ ] 2.1 Create migrations for campaigns, prizes, reward codes, players, spin attempts, spin results, claims, admins, and audit logs.
- [ ] 2.2 Implement Eloquent models, relationships, and validation rules for the campaign domain.
- [ ] 2.3 Add seed data or fixtures for local campaign and admin testing.
- [ ] 2.4 Implement transactional services for quota-aware prize allocation and idempotent claim state transitions.

## 3. Admin Console

- [ ] 3.1 Build admin CRUD flows for campaigns, including activation and scheduling rules.
- [ ] 3.2 Build admin CRUD flows for prizes, including weights, quota limits, and availability state.
- [ ] 3.3 Build admin workflows for reward code creation, import, listing, and status review.
- [ ] 3.4 Build admin views for players, spin histories, and claim records.
- [ ] 3.5 Add audit logging for campaign, prize, reward code, and claim mutations.

## 4. Mobile API

- [ ] 4.1 Implement the current campaign bootstrap endpoint for the mini app.
- [ ] 4.2 Implement the eligibility-check endpoint for reward code and player validation.
- [ ] 4.3 Implement the server-side spin endpoint with persistent spin result storage.
- [ ] 4.4 Implement the claim endpoint with idempotent reward-claim behavior.
- [ ] 4.5 Implement the player history endpoint for prior spins and claim status.

## 5. Hardening And Rollout Readiness

- [ ] 5.1 Add authorization policies and route protection for admin-only functionality.
- [ ] 5.2 Add error handling, request validation, and rate limiting for public mobile APIs.
- [ ] 5.3 Add automated tests for campaign rules, prize allocation, reward code validation, and claim retries.
- [ ] 5.4 Verify the Dockerized stack, database migrations, and staging rollout flow for the first campaign launch.
