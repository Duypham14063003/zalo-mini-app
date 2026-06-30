<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Enums\WorkspaceMembershipRole;
use App\Models\Account;
use App\Models\Game;
use App\Models\GameContentBlock;
use App\Models\GameFormField;
use App\Models\GamePublicId;
use App\Models\GameRedirect;
use App\Models\GameRule;
use App\Models\GameTheme;
use App\Models\IntegrationConnection;
use App\Models\Prize;
use App\Models\RewardCode;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\GameBuilderService;
use App\Services\GameLaunchLinkService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $platformAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'platform_role' => PlatformRole::PlatformAdmin,
                'email_verified_at' => now(),
            ],
        );

        $workspaceOwner = User::query()->updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Workspace Owner',
                'password' => Hash::make('password'),
                'platform_role' => PlatformRole::WorkspaceOwner,
                'email_verified_at' => now(),
            ],
        );

        $account = Account::query()->updateOrCreate(
            ['slug' => 'ohar-demo-account'],
            [
                'owner_user_id' => $workspaceOwner->id,
                'name' => 'OHAR Demo Account',
                'status' => 'active',
                'description' => 'Sample customer account for local lucky wheel development.',
            ],
        );

        $workspace = Workspace::query()->updateOrCreate(
            ['slug' => 'ohar'],
            [
                'account_id' => $account->id,
                'name' => 'OHAR Workspace',
                'status' => 'active',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ],
        );

        WorkspaceMembership::query()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $workspaceOwner->id],
            [
                'role' => WorkspaceMembershipRole::WorkspaceOwner,
                'is_primary' => true,
            ],
        );

        WorkspaceMembership::query()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $platformAdmin->id],
            [
                'role' => WorkspaceMembershipRole::WorkspaceOwner,
                'is_primary' => false,
            ],
        );

        $game = Game::query()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'slug' => 'yeu-thuong'],
            [
                'name' => 'OHAR Yeu Thuong',
                'template_type' => 'lucky_wheel',
                'status' => 'active',
                'description' => 'Sample lucky wheel game for the shared Zalo Mini App.',
                'published_at' => now(),
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
            ],
        );

        GamePublicId::query()->updateOrCreate(
            ['public_id' => 'gm_demo_ohar_yeu_thuong'],
            [
                'workspace_id' => $workspace->id,
                'game_id' => $game->id,
                'slug' => 'ohar-yeu-thuong',
                'is_primary' => true,
                'is_active' => true,
            ],
        );

        GameTheme::query()->updateOrCreate(
            ['game_id' => $game->id],
            [
                'primary_color' => '#f9c667',
                'secondary_color' => '#fff8e4',
                'accent_color' => '#d79e2f',
                'background_style' => 'warm_gradient',
                'theme_tokens' => [
                    'button_color' => '#f9c667',
                    'text_color' => '#6f4910',
                ],
            ],
        );

        foreach ([
            ['block_key' => 'title', 'label' => 'Title', 'content_text' => 'Yeu Thuong', 'sort_order' => 1],
            ['block_key' => 'subtitle', 'label' => 'Subtitle', 'content_text' => 'Uong an lanh, gop ngan', 'sort_order' => 2],
            ['block_key' => 'spin_button', 'label' => 'Spin Button', 'content_text' => 'Quay ngay', 'sort_order' => 3],
        ] as $block) {
            GameContentBlock::query()->updateOrCreate(
                ['game_id' => $game->id, 'block_key' => $block['block_key']],
                [
                    'label' => $block['label'],
                    'content_text' => $block['content_text'],
                    'sort_order' => $block['sort_order'],
                ],
            );
        }

        foreach ([
            ['field_key' => 'reward_code', 'type' => 'text', 'label' => 'Ma du thuong', 'placeholder' => 'Nhap ma du thuong', 'is_required' => true, 'sort_order' => 1],
            ['field_key' => 'phone', 'type' => 'text', 'label' => 'So dien thoai', 'placeholder' => 'Nhap so dien thoai', 'is_required' => true, 'sort_order' => 2],
            ['field_key' => 'full_name', 'type' => 'text', 'label' => 'Ho va ten', 'placeholder' => 'Nhap ho va ten', 'is_required' => true, 'sort_order' => 3],
            ['field_key' => 'district', 'type' => 'select', 'label' => 'Quan huyen', 'placeholder' => null, 'is_required' => true, 'sort_order' => 4, 'options' => ['Quan Binh Tan', 'Quan 1', 'Quan 3', 'Quan 7', 'Thu Duc']],
        ] as $field) {
            GameFormField::query()->updateOrCreate(
                ['game_id' => $game->id, 'field_key' => $field['field_key']],
                [
                    'type' => $field['type'],
                    'label' => $field['label'],
                    'placeholder' => $field['placeholder'],
                    'is_required' => $field['is_required'],
                    'is_active' => true,
                    'sort_order' => $field['sort_order'],
                    'options' => $field['options'] ?? null,
                    'validation_rules' => ['required'],
                ],
            );
        }

        GameRule::query()->updateOrCreate(
            ['game_id' => $game->id],
            [
                'requires_reward_code' => true,
                'max_spins_per_player' => 1,
                'max_uses_per_reward_code' => 1,
                'claim_strategy' => 'instant',
                'redirect_strategy' => 'zalo_oa',
                'rules_payload' => [
                    'allow_repeat_claim' => true,
                ],
            ],
        );

        GameRedirect::query()->updateOrCreate(
            ['game_id' => $game->id, 'is_primary' => true],
            [
                'action' => 'open_oa',
                'target_type' => 'zalo_oa',
                'target_value' => 'https://zalo.me/ohar-demo-oa',
                'fallback_value' => null,
            ],
        );

        foreach ([
            ['code' => 'topup_5k', 'label' => 'TopUp 5K', 'description' => 'Nap ngay vao vi', 'weight' => 30, 'quota' => 500, 'sort_order' => 1],
            ['code' => 'canvas_bag', 'label' => 'Tui canvas', 'description' => 'Phien ban gioi han', 'weight' => 10, 'quota' => 50, 'sort_order' => 2],
            ['code' => 'ohar_bottle', 'label' => 'Chai Ohar', 'description' => 'Qua tang mat lanh', 'weight' => 20, 'quota' => 200, 'sort_order' => 3],
        ] as $prizeData) {
            Prize::query()->updateOrCreate(
                ['game_id' => $game->id, 'code' => $prizeData['code']],
                [
                    'label' => $prizeData['label'],
                    'description' => $prizeData['description'],
                    'inventory_type' => 'quota',
                    'quota' => $prizeData['quota'],
                    'weight' => $prizeData['weight'],
                    'sort_order' => $prizeData['sort_order'],
                    'is_active' => true,
                    'metadata' => [],
                ],
            );
        }

        RewardCode::query()->updateOrCreate(
            ['code' => 'LMAGMPGF'],
            [
                'game_id' => $game->id,
                'status' => 'active',
                'max_uses' => 1,
                'used_count' => 0,
                'metadata' => [
                    'campaign_source' => 'demo_seed',
                ],
            ],
        );

        IntegrationConnection::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'game_id' => $game->id,
                'provider' => 'zalo_oa',
                'connection_key' => 'default_oa',
            ],
            [
                'status' => 'active',
                'config' => [
                    'url' => 'https://zalo.me/ohar-demo-oa',
                ],
            ],
        );

        app(GameBuilderService::class)->ensureConfig($game);

        $launchLinkService = app(GameLaunchLinkService::class);
        $relations = ['publicIds'];

        if ($launchLinkService->tableExists()) {
            $relations[] = 'launchLinks';
        }

        $launchLinkService->syncPublishedLinks($game->fresh($relations));
    }
}
