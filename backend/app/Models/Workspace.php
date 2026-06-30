<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'status',
        'timezone',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
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

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function launchLinks(): HasMany
    {
        return $this->hasMany(GameLaunchLink::class);
    }
}
