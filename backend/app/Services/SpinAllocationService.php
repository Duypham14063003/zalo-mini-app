<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\RewardCodeStatus;
use App\Enums\SpinAttemptStatus;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerSubmission;
use App\Models\Prize;
use App\Models\RewardCode;
use App\Models\SpinAttempt;
use App\Models\SpinResult;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SpinAllocationService
{
    public function allocate(
        Game $game,
        Player $player,
        ?PlayerSubmission $submission = null,
        ?RewardCode $rewardCode = null,
        ?string $idempotencyKey = null,
    ): SpinResult {
        return DB::transaction(function () use ($game, $player, $submission, $rewardCode, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = SpinAttempt::query()
                    ->where('game_id', $game->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->with('result')
                    ->first();

                if ($existing?->result) {
                    return $existing->result;
                }
            }

            $rules = $game->rules;
            $playerAttempts = SpinAttempt::query()
                ->where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->count();

            if ($rules && $playerAttempts >= $rules->max_spins_per_player) {
                throw new DomainException('Player has exhausted the available spins for this game.');
            }

            if ($rules?->requires_reward_code && ! $rewardCode) {
                throw new DomainException('A reward code is required for this game.');
            }

            if ($rewardCode) {
                if ($rewardCode->status !== RewardCodeStatus::Active) {
                    throw new DomainException('Reward code is not active.');
                }

                if ($rewardCode->used_count >= $rewardCode->max_uses) {
                    throw new DomainException('Reward code has reached its usage limit.');
                }
            }

            $attempt = SpinAttempt::create([
                'workspace_id' => $game->workspace_id,
                'game_id' => $game->id,
                'player_id' => $player->id,
                'player_submission_id' => $submission?->id,
                'reward_code_id' => $rewardCode?->id,
                'status' => SpinAttemptStatus::Pending,
                'idempotency_key' => $idempotencyKey,
                'attempted_at' => now(),
            ]);

            $prize = $this->pickPrize($game->id);

            if ($prize) {
                $prize->increment('awarded_count');
            }

            if ($rewardCode) {
                $rewardCode->forceFill([
                    'used_count' => $rewardCode->used_count + 1,
                    'last_used_at' => now(),
                    'last_used_by_player_id' => $player->id,
                    'status' => $rewardCode->used_count + 1 >= $rewardCode->max_uses
                        ? RewardCodeStatus::Exhausted
                        : RewardCodeStatus::Active,
                ])->save();
            }

            $attempt->forceFill([
                'status' => SpinAttemptStatus::Awarded,
            ])->save();

            return SpinResult::create([
                'workspace_id' => $game->workspace_id,
                'game_id' => $game->id,
                'player_id' => $player->id,
                'spin_attempt_id' => $attempt->id,
                'prize_id' => $prize?->id,
                'result_type' => $prize ? 'prize' : 'no_prize',
                'claim_status' => ClaimStatus::Pending,
                'awarded_payload' => $prize ? [
                    'prize_code' => $prize->code,
                    'label' => $prize->label,
                    'description' => $prize->description,
                ] : [
                    'label' => 'No prize',
                ],
                'resolved_at' => now(),
            ]);
        });
    }

    protected function pickPrize(int $gameId): ?Prize
    {
        /** @var Collection<int, Prize> $eligiblePrizes */
        $eligiblePrizes = Prize::query()
            ->where('game_id', $gameId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->filter(function (Prize $prize) {
                if ($prize->weight <= 0) {
                    return false;
                }

                if ($prize->quota === null) {
                    return true;
                }

                return $prize->awarded_count < $prize->quota;
            })
            ->values();

        if ($eligiblePrizes->isEmpty()) {
            return null;
        }

        $totalWeight = $eligiblePrizes->sum('weight');
        $cursor = random_int(1, max(1, $totalWeight));

        foreach ($eligiblePrizes as $prize) {
            $cursor -= $prize->weight;

            if ($cursor <= 0) {
                return $prize;
            }
        }

        return $eligiblePrizes->last();
    }
}
