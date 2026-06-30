<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'block_key',
        'label',
        'content_text',
        'content_payload',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'content_payload' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
