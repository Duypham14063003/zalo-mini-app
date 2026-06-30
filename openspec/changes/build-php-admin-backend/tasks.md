## 1. Admin Shell Redesign

- [x] 1.1 Replace the current Breeze-style admin navigation with a product shell that includes a left navigation rail, top toolbar, and workspace content layout.
- [x] 1.2 Add shared admin UI primitives for cards, stepper headers, builder actions, and management tables so the new shell is consistent across screens.
- [x] 1.3 Update the dashboard and games index screens to use the new shell and present operator-oriented summaries instead of generic CRUD-only blocks.

## 2. Builder Workflow

- [x] 2.1 Refactor the game editing experience into a multi-step builder for general configuration, rewards, wheel design, and final game presentation.
- [x] 2.2 Implement step-aware validation, save actions, and navigation so operators can move between steps without losing context.
- [x] 2.3 Add a live or near-real-time preview panel that reflects edited values during the builder flow.
- [x] 2.4 Add publish-state review behavior so operators can distinguish draft edits from the currently live configuration.

## 3. Wheel Design Configuration

- [x] 3.1 Add persisted backend fields or structures for wheel-design metadata such as palettes, border presets, pointer presets, asset references, and preview tokens.
- [x] 3.2 Build admin controls for selecting wheel presets and editing wheel-specific presentation values.
- [x] 3.3 Map the persisted wheel-design configuration into runtime bootstrap output for published games.

## 4. Missing Admin Management Surfaces

- [x] 4.1 Complete reward code management with creation, bulk input or import-ready flow, listing, and status review.
- [x] 4.2 Complete submissions, spin history, and claim views with filters and operator-friendly record presentation.
- [x] 4.3 Add operator actions for publish or unpublish and any required claim-state or fulfillment review updates.

## 5. Quality And Rollout Readiness

- [x] 5.1 Extend authorization, audit logging, and actor attribution across the new builder and management flows.
- [x] 5.2 Add automated tests for builder step persistence, publish-state behavior, wheel-design persistence, and admin access control.
- [x] 5.3 Verify the redesigned admin and runtime integration end to end with one sample game, including preview, publish, and runtime bootstrap behavior.
