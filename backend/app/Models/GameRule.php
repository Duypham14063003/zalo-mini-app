<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'requires_reward_code',
        'max_spins_per_player',
        'max_uses_per_reward_code',
        'claim_strategy',
        'redirect_strategy',
        'rules_payload',
    ];

    protected function casts(): array
    {
        return [
            'requires_reward_code' => 'boolean',
            'rules_payload' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
