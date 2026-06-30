<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'game_id',
        'player_id',
        'spin_result_id',
        'status',
        'claim_action',
        'redirect_target',
        'claimed_at',
        'fulfilled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'claimed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'metadata' => 'array',
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

    public function spinResult(): BelongsTo
    {
        return $this->belongsTo(SpinResult::class);
    }
}
