<?php

namespace App\Models;

use App\Enums\RewardCodeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'prize_id',
        'last_used_by_player_id',
        'code',
        'status',
        'max_uses',
        'used_count',
        'last_used_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => RewardCodeStatus::class,
            'last_used_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function lastUsedByPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'last_used_by_player_id');
    }
}
