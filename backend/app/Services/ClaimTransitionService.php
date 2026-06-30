<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\GameRedirect;
use App\Models\SpinResult;
use Illuminate\Support\Facades\DB;

class ClaimTransitionService
{
    public function claim(SpinResult $spinResult): Claim
    {
        return DB::transaction(function () use ($spinResult) {
            $lockedResult = SpinResult::query()
                ->with('claim', 'game.redirects')
                ->lockForUpdate()
                ->findOrFail($spinResult->id);

            if ($lockedResult->claim) {
                return $lockedResult->claim;
            }

            $redirect = $lockedResult->game->redirects
                ->sortByDesc(fn (GameRedirect $item) => $item->is_primary)
                ->first();

            $claim = Claim::create([
                'workspace_id' => $lockedResult->workspace_id,
                'game_id' => $lockedResult->game_id,
                'player_id' => $lockedResult->player_id,
                'spin_result_id' => $lockedResult->id,
                'status' => ClaimStatus::Claimed,
                'claim_action' => $redirect?->action,
                'redirect_target' => $redirect?->target_value,
                'claimed_at' => now(),
                'metadata' => [
                    'fallback_value' => $redirect?->fallback_value,
                    'target_type' => $redirect?->target_type,
                ],
            ]);

            $lockedResult->forceFill([
                'claim_status' => ClaimStatus::Claimed,
                'claimed_at' => now(),
            ])->save();

            return $claim;
        });
    }
}
