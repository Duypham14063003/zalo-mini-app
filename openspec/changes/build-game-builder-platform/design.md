## Context

The mini app currently behaves like a fixed promotional experience. It has one wheel layout, one form structure, one reward flow, one visual identity, and no persistent backend authority. The new business goal is to turn this into a service product: customers should eventually be able to sign up, create their own game, adjust branding and rules, collect participant data, and manage outcomes through a backend-driven platform.

Because future customers will expect self-service setup, the backend cannot be modeled only around a single campaign. It should instead treat the current lucky wheel as the first supported game template in a broader game-builder architecture.

## Goals / Non-Goals

**Goals:**
- Establish a Laravel-based backend as the source of truth for accounts, games, themes, form configuration, gameplay rules, prizes, player submissions, spins, claims, and Zalo redirect metadata.
- Keep the first supported game type focused on the current lucky wheel experience while designing the model so more game types can be added later.
- Make the frontend render a game from backend configuration rather than from hardcoded content.
- Support future admin and self-service flows by introducing ownership, permissions, and tenant boundaries from the beginning.
- Keep public gameplay APIs cleanly separated from authenticated management surfaces.

**Non-Goals:**
- Building the complete customer-facing admin UI in the same first step.
- Supporting multiple unrelated game mechanics in the first launch beyond lucky wheel.
- Implementing billing, subscriptions, or package enforcement in the first release.
- Building advanced analytics, dashboards, or marketing automation integrations in the first release.

## Decisions

### 1. Use Laravel as the product backend

Laravel fits the medium-term product direction because the system will need authentication, authorization, server-rendered management UI, queues, validation, file uploads, policies, and modular domain logic. This reduces delivery risk for both the initial API layer and the later admin interface.

### 2. Model the product as a multi-customer platform from day one

Even if the first release behaves like a single-customer system operationally, the data model should include ownership boundaries. A customer account or workspace should own one or more games, and all runtime/player data should be attributable to that owner. This avoids a painful migration when self-service onboarding arrives.

Core ownership shape:
- accounts
- workspaces or tenants
- workspace users
- games

### 3. Treat the lucky wheel as a configurable game template

The backend should not encode one specific campaign experience in flat fields. Instead, it should expose a generic `game` with a `template_type` such as `lucky_wheel`, plus template-specific configuration sections for:
- theme
- content
- form schema
- prizes
- runtime rules
- redirect behavior

This lets the current app evolve into an engine rather than a one-off implementation.

### 4. Split the backend into platform configuration APIs and runtime gameplay APIs

The backend should expose two broad surfaces:
- authenticated management APIs and web routes for customer/admin use
- public runtime APIs for the mini app client

The runtime client only needs the data required to render and execute a game. It should not receive internal-only management fields or privileged operational data.

### 5. Make backend configuration drive frontend rendering

The frontend should load a bootstrap document for a game and render:
- brand colors
- background assets
- copy and labels
- form fields
- prize display data
- CTA labels
- success/failure modal copy
- redirect targets

The frontend still owns layout and animation behavior, but the backend owns configuration and business rules.

### 6. Keep gameplay outcomes server-authoritative

Spin execution, quota enforcement, eligibility validation, reward-code validation, and claim state transitions must happen on the backend. The client may animate the wheel, but it should never decide the winning result.

### 7. Plan the first release around one supported template

The first production slice should support:
- one game template: lucky wheel
- customer ownership
- game configuration persistence
- public runtime APIs
- player submission capture
- spin and claim flow

This creates a product-ready foundation without overcommitting to a full no-code platform immediately.

## Proposed Domain Model

### Platform ownership
- `accounts`: commercial or top-level owner identity
- `workspaces`: isolated customer spaces
- `workspace_users`: users who can manage a workspace
- `workspace_memberships`: roles within a workspace

### Game configuration
- `games`: top-level game record with slug, status, template type, publication state, and scheduling
- `game_themes`: brand tokens such as primary color, secondary color, gradients, typography hints, and imagery references
- `game_content_blocks`: copy for headings, rules, loading messages, popup messages, button labels, and helper text
- `game_form_fields`: declarative field definitions including type, label, placeholder, required flag, options, and display order
- `game_rules`: gameplay settings such as reward code requirement, max spins, claim behavior, and redirect strategy
- `game_redirects`: Zalo OA links, fallback URLs, and success actions

### Gameplay data
- `prizes`: prize label, description, display asset, inventory type, quota, weight, and availability
- `reward_codes`: optional code inventory, ownership, consumption rules, and status
- `players`: participant identity anchored to a specific game/workspace
- `player_submissions`: captured form payload snapshots
- `spin_attempts`: each attempt to execute a game action
- `spin_results`: authoritative outcomes and awarded prize linkage
- `claims`: claim lifecycle and fulfillment status

### Operations
- `audit_logs`: configuration and sensitive state changes
- `integration_connections`: external linkage settings for Zalo or future providers

## API Shape

### Public runtime API

First-release runtime endpoints should include:
- `GET /api/games/{slug}/bootstrap`
- `POST /api/games/{slug}/submissions`
- `POST /api/games/{slug}/eligibility-check`
- `POST /api/games/{slug}/spin`
- `POST /api/games/{slug}/claim`

Example bootstrap payload responsibilities:
- game identity and availability
- theme tokens
- display copy
- form schema
- prize display data
- runtime rule hints safe for clients

### Future management API / admin surface

The design should leave room for:
- account registration and authentication
- workspace creation
- game CRUD
- theme/content/form configuration
- prize and reward-code management
- player submission review
- export/report endpoints

## Frontend Runtime Direction

The mini app should evolve from:
- one component with hardcoded text, colors, prize list, and rules

to:
- a runtime renderer that requests a game bootstrap document
- initializes local UI state from API data
- posts submission data
- triggers server-authoritative spin logic
- renders claim success and redirect behavior from backend-configured outcomes

A minimal runtime contract might look like:

```json
{
  "game": {
    "slug": "ohar-yeu-thuong",
    "templateType": "lucky_wheel",
    "status": "active"
  },
  "theme": {
    "primaryColor": "#f9c667",
    "secondaryColor": "#fff8e4",
    "accentColor": "#d79e2f"
  },
  "content": {
    "title": "Yeu Thuong",
    "subtitle": "Uong an lanh, gop ngan",
    "spinButtonLabel": "Quay ngay"
  },
  "formFields": [],
  "prizes": [],
  "rules": {}
}
```

## Risks / Trade-offs

- [Product modeling is broader than one campaign] -> Mitigation: limit the first release to one template while still modeling ownership and configuration cleanly.
- [Multi-tenant thinking adds complexity early] -> Mitigation: implement tenant-aware schema now but keep operational rollout initially simple.
- [Config-driven UI can become too generic and hard to style] -> Mitigation: keep the first template strongly opinionated and expose only the right customization controls.
- [Admin requirements may expand quickly after the first customer] -> Mitigation: separate platform domain clearly so new screens do not require schema redesign.
- [Zalo-specific behaviors may constrain generalization] -> Mitigation: isolate Zalo redirect and integration data in dedicated configuration records.

## Migration Plan

1. Create a new Laravel backend for the product with Dockerized PostgreSQL.
2. Model ownership, game configuration, and runtime gameplay entities.
3. Implement the public runtime API for a single `lucky_wheel` template.
4. Refactor the frontend to render from bootstrap data instead of hardcoded campaign values.
5. Add authenticated management capabilities for internal operators first.
6. Expand those management flows into customer self-service after the platform model is stable.

## Open Questions

- Should the first release support only one internal customer, with multi-tenant schema hidden behind the scenes, or should visible customer registration exist immediately?
- Do customers need fully free-form layout control, or only brand/theme/content controls within a fixed lucky-wheel template?
- Will player data need export, webhook delivery, or CRM sync in the first operational release?
- Is one Zalo integration per game enough, or does each game need multiple post-claim destinations?
- Should reward codes be optional at the game level, or always part of the lucky-wheel template?
