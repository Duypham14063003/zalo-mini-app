<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameFormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'field_key',
        'type',
        'label',
        'placeholder',
        'help_text',
        'is_required',
        'is_active',
        'options',
        'validation_rules',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'array',
            'validation_rules' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
