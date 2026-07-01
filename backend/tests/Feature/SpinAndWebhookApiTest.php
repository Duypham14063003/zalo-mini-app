<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\GameRule;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpinAndWebhookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_spin_endpoint_returns_weighted_reward_and_creates_spin_log(): void
    {
        [$player, $reward] = $this->createSpinFixture();

        $response = $this->postJson('/api/spin', [
            'userId' => $player->public_id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reward.id', $reward->id)
            ->assertJsonPath('reward.name', $reward->label)
            ->assertJsonPath('reward.type', $reward->inventory_type);

        $this->assertDatabaseHas('spin_logs', [
            'player_id' => $player->id,
            'prize_id' => $reward->id,
            'reward_name' => $reward->label,
        ]);

        $this->assertSame(1, SpinLog::query()->count());
        $this->assertDatabaseHas('spin_results', [
            'player_id' => $player->id,
            'prize_id' => $reward->id,
        ]);
    }

    public function test_webhook_revoke_marks_player_as_revoked(): void
    {
        [$player] = $this->createSpinFixture();

        $response = $this->postJson('/api/zalo/webhook', [
            'event_name' => 'user_revoke_consent',
            'user_id' => $player->public_id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'status' => 'revoked',
        ]);
    }

    public function test_webhook_delete_data_removes_player_and_spin_history(): void
    {
        [$player] = $this->createSpinFixture();

        $this->postJson('/api/spin', [
            'userId' => $player->public_id,
        ])->assertOk();

        $response = $this->postJson('/api/zalo/webhook', [
            'event_name' => 'user_delete_data',
            'user_id' => $player->public_id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('players', [
            'id' => $player->id,
        ]);
        $this->assertDatabaseCount('spin_logs', 0);
        $this->assertDatabaseCount('spin_attempts', 0);
        $this->assertDatabaseCount('spin_results', 0);
    }

    /**
     * @return array{0: Player, 1: Prize}
     */
    protected function createSpinFixture(): array
    {
        $owner = User::factory()->create();
        $account = Account::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Test Account',
            'slug' => 'test-account-'.Str::lower(Str::random(5)),
            'status' => 'active',
        ]);
        $workspace = Workspace::query()->create([
            'account_id' => $account->id,
            'name' => 'Test Workspace',
            'slug' => 'test-workspace-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);
        $game = Game::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Spin Test',
            'slug' => 'spin-test-'.Str::lower(Str::random(5)),
            'template_type' => 'lucky_wheel',
            'status' => 'active',
            'published_at' => now(),
        ]);

        GameRule::query()->create([
            'game_id' => $game->id,
            'max_spins_per_player' => 1,
        ]);

        $reward = Prize::query()->create([
            'game_id' => $game->id,
            'code' => 'voucher-10',
            'label' => 'Voucher 10%',
            'inventory_type' => 'voucher',
            'quota' => 10,
            'weight' => 100,
            'is_active' => true,
        ]);

        $player = Player::query()->create([
            'workspace_id' => $workspace->id,
            'game_id' => $game->id,
            'public_id' => 'player-'.Str::uuid(),
            'full_name' => 'Test Player',
            'zalo_user_id' => 'zalo-'.Str::uuid(),
            'status' => 'active',
        ]);

        return [$player, $reward];
    }
}
