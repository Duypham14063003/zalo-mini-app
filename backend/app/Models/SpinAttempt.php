<?php

namespace App\Models;

use App\Enums\SpinAttemptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpinAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'game_id',
        'player_id',
        'player_submission_id',
        'reward_code_id',
        'status',
        'failure_reason',
        'idempotency_key',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SpinAttemptStatus::class,
            'attempted_at' => 'datetime',
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

    public function playerSubmission(): BelongsTo
    {
        return $this->belongsTo(PlayerSubmission::class);
    }

    public function rewardCode(): BelongsTo
    {
        return $this->belongsTo(RewardCode::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(SpinResult::class);
    }
}
