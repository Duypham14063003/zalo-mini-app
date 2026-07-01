<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'game_id',
        'public_id',
        'full_name',
        'phone',
        'email',
        'zalo_user_id',
        'status',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(PlayerSubmission::class);
    }

    public function spinAttempts(): HasMany
    {
        return $this->hasMany(SpinAttempt::class);
    }

    public function spinResults(): HasMany
    {
        return $this->hasMany(SpinResult::class);
    }

    public function spinLogs(): HasMany
    {
        return $this->hasMany(SpinLog::class);
    }
}
