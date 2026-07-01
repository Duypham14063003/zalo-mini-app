<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\SpinAttemptStatus;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinAttempt;
use App\Models\SpinLog;
use App\Models\SpinResult;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SpinService
{
    /**
     * @return array{success:true,reward:array{id:int,name:string,type:string|null}}
     */
    public function spin(string $userId): array
    {
        return DB::transaction(function () use ($userId) {
            $player = $this->resolvePlayer($userId);

            if (! $player) {
                throw new DomainException('User not found.');
            }

            if ($player->status !== 'active') {
                throw new DomainException('User is not eligible to spin.');
            }

            $game = $player->game()->with('rules')->firstOrFail();
            $maxSpins = (int) ($game->rules?->max_spins_per_player ?? 0);
            $usedSpins = SpinAttempt::query()
                ->where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->count();
            $remainingSpins = max(0, $maxSpins - $usedSpins);

            if ($remainingSpins < 1) {
                throw new DomainException('User has no spins remaining.');
            }

            $reward = $this->pickReward($game->id);

            if (! $reward) {
                throw new DomainException('No reward is available.');
            }

            $attempt = SpinAttempt::create([
                'workspace_id' => $game->workspace_id,
                'game_id' => $game->id,
                'player_id' => $player->id,
                'status' => SpinAttemptStatus::Awarded,
                'attempted_at' => now(),
            ]);

            $reward->increment('awarded_count');

            $result = SpinResult::create([
                'workspace_id' => $game->workspace_id,
                'game_id' => $game->id,
                'player_id' => $player->id,
                'spin_attempt_id' => $attempt->id,
                'prize_id' => $reward->id,
                'result_type' => 'prize',
                'claim_status' => ClaimStatus::Pending,
                'awarded_payload' => [
                    'prize_code' => $reward->code,
                    'label' => $reward->label,
                    'description' => $reward->description,
                    'remaining_spins' => $remainingSpins - 1,
                    'remaining_quantity' => $reward->quota !== null
                        ? max(0, ($reward->quota - ($reward->awarded_count + 1)))
                        : null,
                ],
                'resolved_at' => now(),
            ]);

            SpinLog::create([
                'workspace_id' => $game->workspace_id,
                'game_id' => $game->id,
                'player_id' => $player->id,
                'spin_attempt_id' => $attempt->id,
                'spin_result_id' => $result->id,
                'prize_id' => $reward->id,
                'user_identifier' => $userId,
                'reward_name' => $reward->label,
                'reward_type' => $reward->inventory_type,
                'payload' => [
                    'player_public_id' => $player->public_id,
                    'zalo_user_id' => $player->zalo_user_id,
                    'remaining_spins_before' => $remainingSpins,
                    'remaining_spins_after' => $remainingSpins - 1,
                    'reward_code' => $reward->code,
                ],
                'spun_at' => now(),
            ]);

            return [
                'success' => true,
                'reward' => [
                    'id' => $reward->id,
                    'name' => $reward->label,
                    'type' => $reward->inventory_type,
                ],
            ];
        });
    }

    protected function resolvePlayer(string $userId): ?Player
    {
        return Player::query()
            ->with('game.rules')
            ->where('public_id', $userId)
            ->orWhere('zalo_user_id', $userId)
            ->first();
    }

    protected function pickReward(int $gameId): ?Prize
    {
        /** @var Collection<int, Prize> $eligibleRewards */
        $eligibleRewards = Prize::query()
            ->where('game_id', $gameId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->filter(function (Prize $reward) {
                if ($reward->weight <= 0) {
                    return false;
                }

                if ($reward->quota === null) {
                    return true;
                }

                return $reward->awarded_count < $reward->quota;
            })
            ->values();

        if ($eligibleRewards->isEmpty()) {
            return null;
        }

        $totalWeight = (int) $eligibleRewards->sum('weight');
        $cursor = random_int(1, max(1, $totalWeight));

        foreach ($eligibleRewards as $reward) {
            $cursor -= $reward->weight;

            if ($cursor <= 0) {
                return $reward;
            }
        }

        return $eligibleRewards->last();
    }
}
