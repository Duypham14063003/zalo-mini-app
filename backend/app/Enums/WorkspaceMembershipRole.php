<?php

namespace App\Enums;

enum WorkspaceMembershipRole: string
{
    case WorkspaceOwner = 'workspace_owner';
    case WorkspaceStaff = 'workspace_staff';
}
