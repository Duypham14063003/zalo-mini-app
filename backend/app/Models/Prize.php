<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prize extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'code',
        'label',
        'description',
        'image_asset_path',
        'inventory_type',
        'quota',
        'awarded_count',
        'weight',
        'value_label',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function rewardCodes(): HasMany
    {
        return $this->hasMany(RewardCode::class);
    }

    public function spinLogs(): HasMany
    {
        return $this->hasMany(SpinLog::class);
    }
}
