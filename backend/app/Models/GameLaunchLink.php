<?php

namespace App\Models;

use App\Enums\GameLaunchChannel;
use App\Enums\GameLaunchEntryType;
use App\Enums\GameLaunchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameLaunchLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'game_id',
        'channel',
        'entry_type',
        'public_identifier',
        'miniapp_path',
        'launch_url',
        'qr_payload',
        'qr_asset_path',
        'status',
        'metadata',
        'generated_at',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => GameLaunchChannel::class,
            'entry_type' => GameLaunchEntryType::class,
            'status' => GameLaunchStatus::class,
            'metadata' => 'array',
            'generated_at' => 'datetime',
            'last_verified_at' => 'datetime',
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
}
