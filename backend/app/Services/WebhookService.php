<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Facades\DB;

class WebhookService
{
    /**
     * @return array{success:true}
     */
    public function handle(string $eventName, string $userId): array
    {
        DB::transaction(function () use ($eventName, $userId) {
            $player = $this->resolvePlayer($userId);

            if (! $player) {
                return;
            }

            if ($eventName === 'user_revoke_consent') {
                $player->update([
                    'status' => 'revoked',
                ]);

                return;
            }

            if ($eventName === 'user_delete_data') {
                $player->delete();
            }
        });

        return [
            'success' => true,
        ];
    }

    protected function resolvePlayer(string $userId): ?Player
    {
        return Player::query()
            ->where('public_id', $userId)
            ->orWhere('zalo_user_id', $userId)
            ->first();
    }
}
