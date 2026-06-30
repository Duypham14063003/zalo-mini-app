<?php

namespace App\Models;

use App\Enums\WorkspaceMembershipRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'role' => WorkspaceMembershipRole::class,
            'is_primary' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
