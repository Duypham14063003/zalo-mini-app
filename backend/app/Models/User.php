<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PlatformRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'platform_role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'platform_role' => PlatformRole::class,
        ];
    }

    public function isPlatformAdmin(): bool
    {
        return $this->platform_role === PlatformRole::PlatformAdmin;
    }

    public function isWorkspaceOwner(): bool
    {
        return $this->platform_role === PlatformRole::WorkspaceOwner;
    }

    public function isWorkspaceStaff(): bool
    {
        return $this->platform_role === PlatformRole::WorkspaceStaff;
    }

    public function ownedAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'owner_user_id');
    }

    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function managesWorkspace(Workspace $workspace): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return $this->workspaceMemberships()
            ->where('workspace_id', $workspace->id)
            ->exists();
    }

    public function managesGame(Game $game): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return $this->workspaceMemberships()
            ->where('workspace_id', $game->workspace_id)
            ->exists();
    }

    /**
     * @return Collection<int, int>
     */
    public function managedWorkspaceIds(): Collection
    {
        return $this->workspaceMemberships()
            ->pluck('workspace_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isPlatformAdmin() || $this->isWorkspaceOwner() || $this->isWorkspaceStaff();
    }
}
