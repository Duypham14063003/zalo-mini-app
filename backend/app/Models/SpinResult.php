<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpinResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'game_id',
        'player_id',
        'spin_attempt_id',
        'prize_id',
        'result_type',
        'claim_status',
        'awarded_payload',
        'resolved_at',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'claim_status' => ClaimStatus::class,
            'awarded_payload' => 'array',
            'resolved_at' => 'datetime',
            'claimed_at' => 'datetime',
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

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function claim(): HasOne
    {
        return $this->hasOne(Claim::class);
    }
}
