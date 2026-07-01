<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpinLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'game_id',
        'player_id',
        'spin_attempt_id',
        'spin_result_id',
        'prize_id',
        'user_identifier',
        'reward_name',
        'reward_type',
        'payload',
        'spun_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'spun_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function spinAttempt(): BelongsTo
    {
        return $this->belongsTo(SpinAttempt::class);
    }

    public function spinResult(): BelongsTo
    {
        return $this->belongsTo(SpinResult::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }
}
