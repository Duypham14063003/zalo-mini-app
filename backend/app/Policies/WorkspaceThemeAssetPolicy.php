<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceThemeAsset;

class WorkspaceThemeAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isWorkspaceOwner() || $user->isWorkspaceStaff();
    }

    public function view(User $user, WorkspaceThemeAsset $workspaceThemeAsset): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isWorkspaceOwner();
    }

    public function update(User $user, WorkspaceThemeAsset $workspaceThemeAsset): bool
    {
        return $user->isPlatformAdmin() || $user->isWorkspaceOwner();
    }

    public function delete(User $user, WorkspaceThemeAsset $workspaceThemeAsset): bool
    {
        return $this->update($user, $workspaceThemeAsset);
    }
}
