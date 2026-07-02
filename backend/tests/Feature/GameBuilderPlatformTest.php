<?php

namespace Tests\Feature;

use App\Enums\GameLaunchChannel;
use App\Enums\GameLaunchStatus;
use App\Enums\PlatformRole;
use App\Filament\Resources\GameResource\Pages\EditGame;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Game;
use App\Models\GameLaunchLink;
use App\Models\GamePublicId;
use App\Models\GameRule;
use App\Models\Player;
use App\Models\Prize;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceThemeAsset;
use App\Services\GameBuilderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GameBuilderPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('game_launch.runtime_base_url', 'https://miniapp.test');
        config()->set('game_launch.runtime_path_template', '/play/{public_id}');
        config()->set('game_launch.zalo_launch_url_template', 'https://zalo.test/open?app=shared-mini-app&path={path}&game={public_id}');

        $this->seed(DatabaseSeeder::class);
    }

    public function test_bootstrap_resolves_slug_and_public_identifier(): void
    {
        $bySlug = $this->getJson('/api/games/ohar-yeu-thuong/bootstrap');

        $bySlug
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('game.slug', 'ohar-yeu-thuong')
            ->assertJsonPath('theme.primary_color', '#f9c667')
            ->assertJsonPath('theme.wheel.borderPreset', 'pink-star')
            ->assertJsonPath('content.title', 'Yeu Thuong');

        $byPublicId = $this->getJson('/api/games/gm_demo_ohar_yeu_thuong/bootstrap');

        $byPublicId
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('game.publicIdentifier', 'gm_demo_ohar_yeu_thuong');
    }

    public function test_submission_rejects_invalid_dynamic_form_payload(): void
    {
        $response = $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'reward_code' => '',
                'phone' => '',
                'full_name' => '',
                'district' => 'District 99',
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'The submitted data is invalid.')
            ->assertJsonStructure([
                'errors' => [
                    'reward_code',
                    'phone',
                    'full_name',
                    'district',
                ],
            ]);
    }

    public function test_spin_rejects_requests_replayed_against_a_different_game_identity(): void
    {
        $submission = $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'reward_code' => 'LMAGMPGF',
                'phone' => '0900000001',
                'full_name' => 'Demo User',
                'district' => 'Quan Binh Tan',
            ],
        ])->assertCreated()->json();

        $secondGame = $this->createSecondaryGame();

        $response = $this->postJson("/api/games/{$secondGame->publicIds()->first()->slug}/spin", [
            'player_public_id' => $submission['playerPublicId'],
            'player_submission_id' => $submission['submissionId'],
            'idempotency_key' => 'cross-game-test',
        ]);

        $response->assertNotFound();
    }

    public function test_submission_reuses_existing_player_when_zalo_user_id_matches(): void
    {
        $firstSubmission = $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'reward_code' => 'LMAGMPGF',
                'phone' => '0900000011',
                'full_name' => 'Lan',
                'district' => 'Quan Binh Tan',
            ],
            'zalo_profile' => [
                'id' => 'zalo-user-001',
                'name' => 'Lan Zalo',
            ],
        ])->assertCreated()->json();

        $secondSubmission = $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'reward_code' => 'LMAGMPGF',
                'phone' => '0900000099',
                'full_name' => 'Lan Nguyen',
                'district' => 'Quan Binh Tan',
            ],
            'zalo_profile' => [
                'id' => 'zalo-user-001',
                'name' => 'Lan Nguyen',
            ],
        ])->assertCreated()->json();

        $player = Player::query()->where('public_id', $firstSubmission['playerPublicId'])->firstOrFail();

        $this->assertSame($firstSubmission['playerPublicId'], $secondSubmission['playerPublicId']);
        $this->assertSame('zalo-user-001', $player->zalo_user_id);
        $this->assertSame('Lan Nguyen', $player->full_name);
        $this->assertSame('0900000099', $player->phone);
        $this->assertSame(1, Player::count());
        $this->assertSame(2, $player->submissions()->count());
    }

    public function test_claim_endpoint_is_idempotent_for_the_same_spin_result(): void
    {
        $submission = $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'reward_code' => 'LMAGMPGF',
                'phone' => '0900000002',
                'full_name' => 'Lucky Player',
                'district' => 'Quan Binh Tan',
            ],
        ])->assertCreated()->json();

        $spin = $this->postJson('/api/games/ohar-yeu-thuong/spin', [
            'player_public_id' => $submission['playerPublicId'],
            'player_submission_id' => $submission['submissionId'],
            'reward_code' => 'LMAGMPGF',
            'idempotency_key' => 'claim-idempotent-test',
        ])->assertOk()->json();

        $firstClaim = $this->postJson('/api/games/ohar-yeu-thuong/claim', [
            'spin_result_id' => $spin['spinResultId'],
        ]);

        $secondClaim = $this->postJson('/api/games/ohar-yeu-thuong/claim', [
            'spin_result_id' => $spin['spinResultId'],
        ]);

        $firstClaim
            ->assertOk()
            ->assertJsonPath('action', 'open_oa');

        $secondClaim
            ->assertOk()
            ->assertJsonPath('claimId', $firstClaim->json('claimId'));

        $this->assertSame(1, Claim::count());
    }

    public function test_workspace_routes_allow_owner_and_platform_admin_but_forbid_unrelated_users(): void
    {
        $workspace = Workspace::query()->where('slug', 'ohar')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $outsider = User::factory()->create([
            'platform_role' => PlatformRole::WorkspaceStaff,
        ]);

        $this->actingAs($owner)
            ->getJson("/workspaces/{$workspace->id}")
            ->assertOk()
            ->assertJsonPath('slug', 'ohar');

        $this->actingAs($admin)
            ->getJson("/workspaces/{$workspace->id}")
            ->assertOk();

        $this->actingAs($outsider)
            ->getJson("/workspaces/{$workspace->id}")
            ->assertForbidden();
    }

    public function test_updating_a_game_records_an_audit_log_with_actor_attribution(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner);
        $game->update([
            'name' => 'OHAR Yeu Thuong Updated',
        ]);

        $log = AuditLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame($owner->id, $log->actor_user_id);
        $this->assertSame('updated', $log->action);
        $this->assertSame((string) $game->id, $log->target_id);
        $this->assertSame('OHAR Yeu Thuong', $log->before_state['name']);
        $this->assertSame('OHAR Yeu Thuong Updated', $log->after_state['name']);
    }

    public function test_builder_general_step_persists_draft_without_changing_runtime_until_publish(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'general',
            'intent' => 'save',
            'general' => [
                'name' => 'Builder Draft Name',
                'slug' => 'ohar-yeu-thuong',
                'status' => 'active',
                'description' => 'Draft update only',
            ],
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'general']));

        $game->refresh();

        $this->assertSame('OHAR Yeu Thuong', $game->name);
        $this->assertSame('Builder Draft Name', $game->builderConfig->draft_config['general']['name']);

        $this->getJson('/api/games/ohar-yeu-thuong/bootstrap')
            ->assertOk()
            ->assertJsonPath('game.name', 'OHAR Yeu Thuong');

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'publish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $this->getJson('/api/games/ohar-yeu-thuong/bootstrap')
            ->assertOk()
            ->assertJsonPath('game.name', 'Builder Draft Name');
    }

    public function test_builder_design_publish_updates_runtime_wheel_configuration(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'design',
            'intent' => 'save',
            'design' => [
                'primary_color' => '#123456',
                'secondary_color' => '#abcdef',
                'accent_color' => '#654321',
                'palette_preset' => 'marine',
                'border_preset' => 'gold-ring',
                'border_asset_path' => 'wheel-borders/custom-ring.png',
                'pointer_preset' => 'triangle-fire',
                'center_label' => 'VIP',
                'background_style' => 'bg_showcase',
                'background_asset_path' => 'wheel-backgrounds/custom-bg.png',
                'preview_note' => 'Preview',
            ],
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'design']));

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'publish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $this->getJson('/api/games/ohar-yeu-thuong/bootstrap')
            ->assertOk()
            ->assertJsonPath('theme.primary_color', '#123456')
            ->assertJsonPath('theme.wheel.borderPreset', 'gold-ring')
            ->assertJsonPath('theme.wheel.borderAssetPath', 'wheel-borders/custom-ring.png')
            ->assertJsonPath('theme.wheel.borderAssetUrl', Storage::disk('public')->url('wheel-borders/custom-ring.png'))
            ->assertJsonPath('theme.background_style', 'bg_showcase')
            ->assertJsonPath('theme.background_asset_path', 'wheel-backgrounds/custom-bg.png')
            ->assertJsonPath('theme.background_asset_url', Storage::disk('public')->url('wheel-backgrounds/custom-bg.png'))
            ->assertJsonPath('theme.wheel.pointerPreset', 'triangle-fire');
    }

    public function test_shared_theme_assets_are_visible_across_accounts(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        WorkspaceThemeAsset::query()->create([
            'workspace_id' => null,
            'slot_type' => 'banner',
            'display_name' => 'Shared Banner Only',
            'asset_path' => 'workspace-theme-assets/banners/shared/shared-banner.svg',
            'source_kind' => 'upload',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($owner)
            ->get("/admin/games/{$game->id}/edit")
            ->assertOk()
            ->assertSee('Banner LanEm')
            ->assertSee('Shared Banner Only');

        $this->assertSame(
            1,
            WorkspaceThemeAsset::query()
                ->whereNull('workspace_id')
                ->where('slot_type', 'banner')
                ->where('display_name', 'Banner LanEm')
                ->count(),
        );
    }

    public function test_builder_asset_library_persists_selected_presets_and_bootstrap_resolves_runtime_assets(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();
        $workspaceAssets = WorkspaceThemeAsset::query()
            ->whereNull('workspace_id')
            ->where('is_active', true)
            ->get()
            ->groupBy('slot_type');

        Storage::disk('public')->put(
            'workspace-theme-assets/banners/shared/custom-banner.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="470" height="200"><rect width="470" height="200" rx="24" fill="#ffffff"/><text x="235" y="110" text-anchor="middle" fill="#e26d5a" font-size="42">Custom Banner</text></svg>',
        );

        $this->actingAs($owner);

        Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
            ->set('data.background_preset_id', $workspaceAssets['background']->first()->id)
            ->set('data.banner_preset_id', $workspaceAssets['banner']->first()->id)
            ->set('data.banner_asset_path', 'workspace-theme-assets/banners/shared/custom-banner.svg')
            ->set('data.spin_button_preset_id', $workspaceAssets['spin_button']->first()->id)
            ->set('data.extra_spin_button_preset_id', $workspaceAssets['extra_spin_button']->first()->id)
            ->set('data.wheel_border_preset_id', $workspaceAssets['wheel_border']->first()->id)
            ->set('data.wheel_pointer_preset_id', $workspaceAssets['wheel_pointer']->first()->id)
            ->call('save')
            ->assertHasNoErrors();

        $game->refresh();

        $this->assertSame(
            $workspaceAssets['background']->first()->id,
            $game->builderConfig->draft_config['design']['background_preset_id'],
        );
        $this->assertSame(
            $workspaceAssets['wheel_border']->first()->id,
            $game->builderConfig->draft_config['design']['wheel_border_preset_id'],
        );
        $this->assertSame(
            'workspace-theme-assets/banners/shared/custom-banner.svg',
            $game->builderConfig->draft_config['design']['banner_asset_path'],
        );

        app(GameBuilderService::class)->publish($game, $game->builderConfig);

        $this->getJson('/api/games/ohar-yeu-thuong/bootstrap')
            ->assertOk()
            ->assertJsonPath('theme.assets.background.presetId', $workspaceAssets['background']->first()->id)
            ->assertJsonPath('theme.assets.background.assetUrl', Storage::disk('public')->url($workspaceAssets['background']->first()->asset_path))
            ->assertJsonPath('theme.assets.banner.source', 'override')
            ->assertJsonPath('theme.assets.banner.assetUrl', Storage::disk('public')->url('workspace-theme-assets/banners/shared/custom-banner.svg'))
            ->assertJsonPath('theme.assets.spin_button.presetId', $workspaceAssets['spin_button']->first()->id)
            ->assertJsonPath('theme.assets.extra_spin_button.presetId', $workspaceAssets['extra_spin_button']->first()->id)
            ->assertJsonPath('theme.assets.wheel_border.presetId', $workspaceAssets['wheel_border']->first()->id)
            ->assertJsonPath('theme.assets.wheel_pointer.presetId', $workspaceAssets['wheel_pointer']->first()->id)
            ->assertJsonPath('theme.wheel.pointerAssetUrl', Storage::disk('public')->url($workspaceAssets['wheel_pointer']->first()->asset_path));
    }

    public function test_publish_generates_launch_links_for_preview_and_zalo_channels(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();
        $game->launchLinks()->delete();

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'publish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $this->assertDatabaseHas('game_launch_links', [
            'game_id' => $game->id,
            'channel' => GameLaunchChannel::WebPreview->value,
            'public_identifier' => 'gm_demo_ohar_yeu_thuong',
            'status' => GameLaunchStatus::Ready->value,
        ]);

        $this->assertDatabaseHas('game_launch_links', [
            'game_id' => $game->id,
            'channel' => GameLaunchChannel::ZaloMiniApp->value,
            'public_identifier' => 'gm_demo_ohar_yeu_thuong',
            'status' => GameLaunchStatus::Ready->value,
        ]);

        $webPreview = GameLaunchLink::query()
            ->where('game_id', $game->id)
            ->where('channel', GameLaunchChannel::WebPreview->value)
            ->firstOrFail();

        $this->assertSame('/play/gm_demo_ohar_yeu_thuong', $webPreview->miniapp_path);
        $this->assertSame('https://miniapp.test/play/gm_demo_ohar_yeu_thuong', $webPreview->launch_url);
    }

    public function test_republishing_refreshes_existing_launch_links_without_duplicates(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();
        $originalLaunchLink = GameLaunchLink::query()
            ->where('game_id', $game->id)
            ->where('channel', GameLaunchChannel::ZaloMiniApp->value)
            ->firstOrFail();

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'general',
            'intent' => 'save',
            'general' => [
                'name' => 'Republish Test Name',
                'slug' => 'ohar-yeu-thuong',
                'status' => 'active',
                'description' => 'Republish test',
            ],
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'general']));

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'publish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $refreshedLaunchLink = GameLaunchLink::query()
            ->where('game_id', $game->id)
            ->where('channel', GameLaunchChannel::ZaloMiniApp->value)
            ->firstOrFail();

        $this->assertSame($originalLaunchLink->id, $refreshedLaunchLink->id);
        $this->assertSame(2, GameLaunchLink::query()->where('game_id', $game->id)->count());
        $this->assertNotNull($refreshedLaunchLink->generated_at);
    }

    public function test_publish_generates_prize_label_when_only_code_is_provided(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'rewards',
            'intent' => 'save',
            'rewards' => [
                'requires_reward_code' => true,
                'max_spins_per_player' => 1,
                'prizes' => [
                    [
                        'code' => 'VOUCHER_50K',
                        'label' => '',
                        'description' => null,
                        'quota' => null,
                        'weight' => 100,
                        'is_active' => true,
                    ],
                ],
            ],
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'rewards']));

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'publish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $this->assertDatabaseHas('prizes', [
            'game_id' => $game->id,
            'code' => 'VOUCHER_50K',
            'label' => 'Voucher 50K',
        ]);
    }

    public function test_launch_link_can_be_marked_invalid_when_zalo_template_is_missing(): void
    {
        config()->set('game_launch.zalo_launch_url_template', null);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'publish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $this->assertDatabaseHas('game_launch_links', [
            'game_id' => $game->id,
            'channel' => GameLaunchChannel::ZaloMiniApp->value,
            'status' => GameLaunchStatus::Invalid->value,
        ]);
    }

    public function test_unpublish_archives_launch_links_for_distribution_channels(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'unpublish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $this->assertDatabaseHas('game_launch_links', [
            'game_id' => $game->id,
            'channel' => GameLaunchChannel::WebPreview->value,
            'status' => GameLaunchStatus::Archived->value,
        ]);

        $this->assertDatabaseHas('game_launch_links', [
            'game_id' => $game->id,
            'channel' => GameLaunchChannel::ZaloMiniApp->value,
            'status' => GameLaunchStatus::Archived->value,
        ]);
    }

    public function test_filament_edit_page_shows_launch_metadata_after_publish(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner)
            ->get("/admin/games/{$game->id}/edit")
            ->assertOk()
            ->assertSee('Liên kết triển khai')
            ->assertSee('Link xem trước')
            ->assertSee('Link mở trong Zalo');

        Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
            ->assertSet('data.launch_public_identifier', 'gm_demo_ohar_yeu_thuong')
            ->assertSet('data.launch_runtime_url', 'https://miniapp.test/play/gm_demo_ohar_yeu_thuong')
            ->assertSet('data.launch_zalo_url', 'https://zalo.test/open?app=shared-mini-app&path=%2Fplay%2Fgm_demo_ohar_yeu_thuong&game=gm_demo_ohar_yeu_thuong');
    }

    public function test_filament_save_persists_changed_fields_into_runtime_tables(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();
        $emailFieldId = $game->formFields()->where('field_key', 'full_name')->value('id');

        $this->actingAs($owner);

        Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
            ->set('data.name', 'Game Save Runtime')
            ->set('data.game_description', 'Da luu vao bang runtime')
            ->set('data.title', 'Tieu de moi')
            ->set('data.requires_reward_code', false)
            ->set('data.max_spins_per_player', 3)
            ->set('data.form_fields', [
                [
                    'id' => $emailFieldId,
                    'field_key' => 'full_name',
                    'type' => 'text',
                    'label' => 'Ho va ten day du',
                    'placeholder' => 'Nhap day du',
                    'help_text' => 'Thong tin lien he',
                    'options_text' => '',
                    'is_required' => true,
                    'is_active' => true,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $game->refresh();

        $this->assertSame('Game Save Runtime', $game->name);
        $this->assertSame('Da luu vao bang runtime', $game->description);
        $this->assertSame('Tieu de moi', $game->contentBlocks()->where('block_key', 'title')->value('content_text'));
        $this->assertSame(3, $game->rules()->value('max_spins_per_player'));
        $this->assertFalse((bool) $game->rules()->value('requires_reward_code'));
        $this->assertDatabaseHas('game_form_fields', [
            'id' => $emailFieldId,
            'field_key' => 'full_name',
            'label' => 'Ho va ten day du',
            'placeholder' => 'Nhap day du',
            'help_text' => 'Thong tin lien he',
        ]);
    }

    public function test_legacy_builder_edit_page_shows_launch_metadata_and_regenerate_action(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('games.edit', ['game' => $game, 'step' => 'publish']))
            ->assertOk()
            ->assertSee('Launch links')
            ->assertSee('Tao lai link')
            ->assertSee('gm_demo_ohar_yeu_thuong')
            ->assertSee('https://miniapp.test/play/gm_demo_ohar_yeu_thuong');
    }

    public function test_runtime_returns_unavailable_for_unpublished_public_identifier(): void
    {
        $game = $this->createSecondaryGame();
        $game->builderConfig()->create([
            'active_step' => 'general',
            'publication_status' => 'draft',
            'draft_config' => [],
            'published_config' => null,
            'last_saved_at' => now(),
            'published_at' => null,
        ]);

        $this->getJson('/api/games/gm_second_game/bootstrap')
            ->assertStatus(404)
            ->assertJsonPath('available', false)
            ->assertJsonPath('message', 'Game is unavailable.');
    }

    public function test_unrelated_workspace_user_cannot_access_builder_routes(): void
    {
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();
        $outsider = User::factory()->create([
            'platform_role' => PlatformRole::WorkspaceStaff,
        ]);

        $this->actingAs($outsider)
            ->get(route('games.edit', $game))
            ->assertForbidden();
    }

    public function test_fulfilling_a_claim_updates_related_spin_result_status(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $submission = $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'reward_code' => 'LMAGMPGF',
                'phone' => '0900001111',
                'full_name' => 'Claim Review User',
                'district' => 'Quan Binh Tan',
            ],
        ])->assertCreated()->json();

        $spin = $this->postJson('/api/games/ohar-yeu-thuong/spin', [
            'player_public_id' => $submission['playerPublicId'],
            'player_submission_id' => $submission['submissionId'],
            'reward_code' => 'LMAGMPGF',
            'idempotency_key' => 'fulfill-claim-status-test',
        ])->assertOk()->json();

        $claimResponse = $this->postJson('/api/games/ohar-yeu-thuong/claim', [
            'spin_result_id' => $spin['spinResultId'],
        ])->assertOk()->json();

        $claim = Claim::query()->findOrFail($claimResponse['claimId']);

        $this->actingAs($owner)
            ->patch(route('games.claims.fulfill', ['game' => $claim->game_id, 'claim' => $claim->id]))
            ->assertRedirect(route('games.claims', $claim->game_id));

        $claim->refresh();

        $this->assertSame('fulfilled', $claim->status->value);
        $this->assertSame('fulfilled', $claim->spinResult->fresh()->claim_status->value);
    }

    public function test_submissions_screen_supports_partial_keyword_search(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'reward_code' => 'LMAGMPGF',
                'phone' => '0901234567',
                'full_name' => 'Duy Search User',
                'district' => 'Quan Binh Tan',
            ],
        ])->assertCreated();

        $this->actingAs($owner)
            ->get(route('games.submissions', ['game' => $game, 'q' => 'Duy Sea']))
            ->assertOk()
            ->assertSee('Duy Search User')
            ->assertSee('0901234567');
    }

    public function test_builder_publish_can_add_and_remove_form_fields(): void
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();
        $existingFields = $game->formFields()->orderBy('sort_order')->get()->keyBy('field_key');

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'save',
            'presentation' => [
                'title' => 'Yeu Thuong',
                'subtitle' => 'Uong an lanh, gop ngan',
                'description' => 'Cap nhat field',
                'spin_button' => 'Quay ngay',
                'continue_button' => 'Nhan thuong',
                'loading_message' => 'Dang tai...',
                'redirect' => [
                    'action' => 'open_oa',
                    'target_type' => 'zalo_oa',
                    'target_value' => 'https://zalo.me/ohar-demo-oa',
                    'fallback_value' => null,
                ],
                'fields' => [
                    [
                        'id' => $existingFields['reward_code']->id,
                        'field_key' => 'reward_code',
                        'type' => 'text',
                        'label' => 'Ma du thuong',
                        'placeholder' => 'Nhap ma du thuong',
                        'help_text' => null,
                        'is_required' => '1',
                        'is_active' => '1',
                    ],
                    [
                        'id' => $existingFields['phone']->id,
                        'field_key' => 'phone',
                        'type' => 'tel',
                        'label' => 'So dien thoai',
                        'placeholder' => 'Nhap so dien thoai',
                        'help_text' => null,
                        'is_required' => '1',
                        'is_active' => '1',
                    ],
                    [
                        'id' => $existingFields['full_name']->id,
                        'field_key' => 'full_name',
                        'type' => 'text',
                        'label' => 'Ho va ten',
                        'placeholder' => 'Nhap ho va ten',
                        'help_text' => null,
                        'is_required' => '1',
                        'is_active' => '1',
                    ],
                    [
                        'id' => $existingFields['district']->id,
                        'field_key' => 'district',
                        'type' => 'select',
                        'label' => 'Quan huyen',
                        'placeholder' => null,
                        'help_text' => null,
                        'is_required' => '1',
                        'is_active' => '1',
                        'options' => "Quan Binh Tan\nQuan 1",
                        'remove' => '1',
                    ],
                    [
                        'field_key' => 'email',
                        'type' => 'text',
                        'label' => 'Email',
                        'placeholder' => 'Nhap email',
                        'help_text' => 'De nhan thong bao',
                        'is_active' => '1',
                    ],
                ],
            ],
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $this->actingAs($owner)->patch(route('games.update', $game), [
            'step' => 'publish',
            'intent' => 'publish',
        ])->assertRedirect(route('games.edit', ['game' => $game, 'step' => 'publish']));

        $bootstrap = $this->getJson('/api/games/ohar-yeu-thuong/bootstrap')->assertOk()->json();
        $fieldKeys = collect($bootstrap['formFields'])->pluck('fieldKey')->all();

        $this->assertContains('email', $fieldKeys);
        $this->assertNotContains('district', $fieldKeys);

        $this->assertDatabaseHas('game_form_fields', [
            'game_id' => $game->id,
            'field_key' => 'email',
            'label' => 'Email',
        ]);

        $this->assertDatabaseMissing('game_form_fields', [
            'game_id' => $game->id,
            'field_key' => 'district',
        ]);
    }

    public function test_eligibility_accepts_custom_reward_code_field_keys(): void
    {
        $game = Game::query()->where('slug', 'yeu-thuong')->firstOrFail();

        $game->formFields()->where('field_key', 'reward_code')->update([
            'field_key' => 'campaign_code',
            'label' => 'Ma du thuong',
            'placeholder' => 'Nhap ma du thuong',
        ]);

        $submission = $this->postJson('/api/games/ohar-yeu-thuong/submissions', [
            'payload' => [
                'campaign_code' => 'LMAGMPGF',
                'phone' => '0909999999',
                'full_name' => 'Custom Reward Code User',
                'district' => 'Quan Binh Tan',
            ],
        ])->assertCreated()->json();

        $this->postJson('/api/games/ohar-yeu-thuong/eligibility-check', [
            'player_public_id' => $submission['playerPublicId'],
        ])->assertOk()
            ->assertJsonPath('eligible', true)
            ->assertJsonPath('remainingSpins', 1);

        $this->postJson('/api/games/ohar-yeu-thuong/spin', [
            'player_public_id' => $submission['playerPublicId'],
            'player_submission_id' => $submission['submissionId'],
            'idempotency_key' => 'custom-reward-code-field',
        ])->assertOk()
            ->assertJsonStructure([
                'spinResultId',
                'prize' => ['code', 'label'],
            ]);
    }

    protected function createSecondaryGame(): Game
    {
        $account = Account::create([
            'name' => 'Second Account',
            'slug' => 'second-account',
            'status' => 'active',
        ]);

        $workspace = Workspace::create([
            'account_id' => $account->id,
            'name' => 'Second Workspace',
            'slug' => 'second-workspace',
            'status' => 'active',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $game = Game::create([
            'workspace_id' => $workspace->id,
            'name' => 'Second Lucky Wheel',
            'slug' => 'second-wheel',
            'template_type' => 'lucky_wheel',
            'status' => 'active',
            'published_at' => now(),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        GamePublicId::create([
            'workspace_id' => $workspace->id,
            'game_id' => $game->id,
            'public_id' => 'gm_second_game',
            'slug' => 'second-wheel-public',
            'is_primary' => true,
            'is_active' => true,
        ]);

        GameRule::create([
            'game_id' => $game->id,
            'requires_reward_code' => false,
            'max_spins_per_player' => 1,
            'claim_strategy' => 'instant',
            'redirect_strategy' => 'close_app',
        ]);

        Prize::create([
            'game_id' => $game->id,
            'code' => 'second-prize',
            'label' => 'Second Prize',
            'inventory_type' => 'quota',
            'quota' => 5,
            'weight' => 100,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return $game;
    }
}
