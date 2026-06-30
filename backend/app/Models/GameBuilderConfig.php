<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameBuilderConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'active_step',
        'publication_status',
        'draft_config',
        'published_config',
        'last_saved_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'draft_config' => 'array',
            'published_config' => 'array',
            'last_saved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
