<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_style',
        'background_asset_path',
        'logo_asset_path',
        'theme_tokens',
    ];

    protected function casts(): array
    {
        return [
            'theme_tokens' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
