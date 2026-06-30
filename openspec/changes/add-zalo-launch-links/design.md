## Context

The Laravel backend now supports game creation, builder drafts, runtime publication, and public bootstrap APIs. A lucky wheel game can already be published into runtime tables, but there is still no productized way to distribute that published game to an end user through the shared Zalo Mini App. Operators must currently invent launch links outside the system, which means the publish flow stops before the actual campaign entry point is ready.

The current runtime contract already resolves a game by public identifier in `GET /api/games/{publicIdentifier}/bootstrap`, and each game already has a `game_public_ids` record with both `public_id` and `slug`. That means the missing architecture is not game resolution itself; it is the layer that translates a published game into deployment-ready launch metadata for web preview, Mini App entry, Zalo sharing, and QR distribution.

This change sits across multiple modules:
- publish logic in Laravel domain services
- persistent launch metadata in PostgreSQL
- operator actions and feedback in Filament admin
- runtime route conventions in the shared Mini App frontend

It also carries an integration constraint: the platform may not always have a production-ready Zalo launch template configured in every environment. The design therefore needs a graceful distinction between internal publication and external launch readiness.

## Goals / Non-Goals

**Goals:**
- Generate durable launch metadata whenever a game is published, using a stable public identifier.
- Support a shared Mini App model where one deployed Mini App can open many different games.
- Persist launch records separately from general game configuration so multiple distribution channels can be added without overloading the game table.
- Expose publish-time outputs in admin, including runtime URL, Mini App entry path, Zalo launch URL, QR payload, and readiness state.
- Keep internal publication state independent from whether a Zalo launch URL is fully usable in a given environment.

**Non-Goals:**
- Implementing every Zalo-specific API integration detail or assuming a production Zalo launch template already exists.
- Creating one separate Mini App per workspace or per game.
- Replacing the existing runtime bootstrap API contract.
- Building full analytics for QR scans, shares, or channel attribution in this change.

## Decisions

### 1. Use one shared Mini App and route into games by `public_id`

The platform SHALL treat the deployed Zalo Mini App as a shared shell that opens different games based on a stable public identifier, rather than creating separate Mini Apps per customer or per campaign.

Why:
- it matches the platform goal of serving many customer campaigns from one product
- it avoids repeated Zalo app registration and review overhead
- it preserves links even if a game name or slug changes later

The canonical launch identifier SHALL be `game_public_ids.public_id`, while `slug` remains a human-friendly alias.

Alternatives considered:
- Use `slug` as the canonical entry key: easier to read, but fragile when naming changes.
- Register one Mini App per customer: not operationally scalable for a shared platform.

### 2. Store launch metadata in a dedicated `game_launch_links` table

Launch metadata SHALL live in a separate table rather than being appended to `games` or `game_public_ids`.

Suggested fields:
- `game_id`
- `workspace_id`
- `channel`
- `entry_type`
- `public_identifier`
- `miniapp_path`
- `launch_url`
- `qr_payload`
- `qr_asset_path` or null
- `status`
- `metadata`
- `generated_at`
- `last_verified_at`

Why:
- one game can have multiple channels, such as web preview and Zalo Mini App
- launch records have a lifecycle that is different from base game identity
- this keeps future expansion to OA, short links, or print QR outputs clean

Alternatives considered:
- Add launch columns directly to `game_public_ids`: simpler now, but too rigid once multiple channels exist.
- Store launch values only in JSON builder config: poor fit for operational querying and regeneration.

### 3. Treat publish and launch readiness as separate states

The system SHALL keep the existing meaning of `published` as “runtime content is live,” but it SHALL also track whether each external launch channel is ready.

Why:
- a game can be valid for runtime while the Zalo entry template is still missing
- operators need to know whether they can distribute immediately
- this avoids falsely implying that “published” automatically means “safe to hand to customers”

The launch record status should support values such as:
- `draft`
- `ready`
- `invalid`
- `archived`

Alternatives considered:
- Collapse everything into a single publish flag: too coarse and operationally misleading.

### 4. Generate launch artifacts as part of the publish workflow

Publishing SHALL remain the trigger that syncs builder draft data into runtime tables, but it SHALL also ensure a launch record exists and is refreshed for the relevant channels.

Recommended service shape:
- keep `GameBuilderService::publish()` focused on runtime publication
- introduce `GameLaunchLinkService` to create or refresh launch records after publish succeeds

Why:
- separates content publication from distribution assembly
- makes regeneration, validation, and future channel support easier
- keeps responsibilities testable in smaller units

Alternatives considered:
- embed launch generation inside `GameBuilderService`: faster initially, but muddles two separate concerns.

### 5. Derive three distinct outputs from each published game

For the first rollout, the system SHALL derive:
- `runtime_url`: internal or web preview URL that resolves the game directly
- `miniapp_path`: the in-app route, such as `/play/{public_id}`
- `launch_url`: the channel-specific URL to open the shared Mini App for that game

The QR payload SHALL encode the `launch_url` when available; otherwise it may fall back to a preview-safe runtime URL in non-production environments.

Why:
- operators need a testable URL even before Zalo launch is fully wired
- the frontend needs a stable route contract
- QR generation becomes a formatting step rather than a separate game concept

Alternatives considered:
- only store one final URL: too opaque for debugging and harder to adapt across channels.

### 6. Show launch artifacts directly in the admin publish surface

The Filament edit experience SHALL expose a deployment block after publish with:
- current publication status
- current launch readiness per channel
- public identifier
- runtime preview link
- Zalo launch link
- QR payload or rendered QR asset
- regenerate and copy actions

Why:
- operators need to use the result immediately after publishing
- this is the product handoff point from builder to campaign distribution

Alternatives considered:
- hide links in a separate operations page: adds friction to the most common publish workflow.

## Risks / Trade-offs

- [Zalo launch URL format may vary by environment or account setup] → Mitigation: keep launch URL generation template-driven and store readiness separately from runtime publish.
- [Multiple identifiers could create confusion about the “real” game URL] → Mitigation: define `public_id` as the canonical identifier and present `slug` only as a friendly alias.
- [Operators may distribute a preview URL instead of a production Zalo link] → Mitigation: label channels clearly in admin and show readiness warnings when a production launch URL is unavailable.
- [Dedicated launch records add schema complexity] → Mitigation: keep the first table intentionally narrow and scoped to deployable channel artifacts.
- [QR image persistence introduces file storage concerns] → Mitigation: store QR payload first and treat QR asset generation as optional or regenerable.

## Migration Plan

1. Add a new launch-link persistence model and migration.
2. Introduce a launch-link generation service that can build deterministic channel outputs from a published game and environment configuration.
3. Update the publish action to call launch generation after runtime publication succeeds.
4. Expose launch metadata in admin, including copy, regenerate, and readiness messaging.
5. Update the Mini App runtime route contract so shared entry paths resolve a game by `public_id`.
6. Add tests for publish-to-launch generation, runtime resolution, and admin visibility.

Rollback strategy:
- keep launch generation additive to the existing publish flow so publication still works if launch generation is disabled
- preserve published runtime behavior even if launch records are missing or invalid
- allow admin to continue using runtime preview links while Zalo launch generation is being stabilized

## Open Questions

- What exact Zalo launch URL pattern should be treated as the production template in this deployment?
- Should QR images be rendered and stored server-side in this change, or is persisted QR payload sufficient for the first milestone?
- Does the runtime frontend already have a stable `/play/{public_id}` route, or should this change define the exact path contract for the Mini App shell?
- Should launch-link regeneration happen automatically on every publish, or only when identifier- or channel-shaping inputs change?
