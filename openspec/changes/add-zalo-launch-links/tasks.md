## 1. Launch link persistence

- [x] 1.1 Add a `game_launch_links` persistence model and migration for channel-specific launch metadata.
- [x] 1.2 Define launch-link status, channel, and identifier conventions that keep `public_id` as the canonical runtime key.
- [x] 1.3 Add model relationships from games and workspaces to launch-link records.

## 2. Publish-time launch generation

- [x] 2.1 Create a dedicated launch-link generation service that derives runtime URLs, Mini App paths, launch URLs, and QR payloads from a published game.
- [x] 2.2 Update the publish flow to generate or refresh launch metadata after runtime publication succeeds.
- [x] 2.3 Keep launch readiness separate from internal publication state and surface non-ready channel outcomes in the generation result.

## 3. Admin distribution workflow

- [x] 3.1 Extend the Filament game edit experience to show launch metadata after publish, including public identifier, preview link, Zalo launch link, and readiness state.
- [x] 3.2 Add operator actions to copy or regenerate launch artifacts without recreating the game.
- [x] 3.3 Add admin messaging that distinguishes published runtime state from customer-ready Zalo distribution state.

## 4. Shared Mini App entry routing

- [x] 4.1 Define the shared Mini App route contract that opens a published game by canonical `public_id`.
- [x] 4.2 Ensure runtime bootstrap resolution and generated launch URLs use the same identifier contract.
- [x] 4.3 Add unavailable-state handling for missing or unpublished public entry identifiers.

## 5. Verification

- [x] 5.1 Add automated tests for publish-to-launch generation, launch record refresh, and non-ready channel states.
- [x] 5.2 Add automated tests for runtime resolution through canonical public identifiers.
- [x] 5.3 Validate the admin publish experience end to end with one sample game, including launch metadata visibility and regeneration.
