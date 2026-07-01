<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Enums\GameTemplateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'template_type',
        'status',
        'description',
        'starts_at',
        'ends_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'template_type' => GameTemplateType::class,
            'status' => GameStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function publicIds(): HasMany
    {
        return $this->hasMany(GamePublicId::class);
    }

    public function theme(): HasOne
    {
        return $this->hasOne(GameTheme::class);
    }

    public function builderConfig(): HasOne
    {
        return $this->hasOne(GameBuilderConfig::class);
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(GameContentBlock::class);
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(GameFormField::class);
    }

    public function rules(): HasOne
    {
        return $this->hasOne(GameRule::class);
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(GameRedirect::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class);
    }

    public function rewardCodes(): HasMany
    {
        return $this->hasMany(RewardCode::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function spinResults(): HasMany
    {
        return $this->hasMany(SpinResult::class);
    }

    public function spinLogs(): HasMany
    {
        return $this->hasMany(SpinLog::class);
    }

    public function launchLinks(): HasMany
    {
        return $this->hasMany(GameLaunchLink::class);
    }
}
