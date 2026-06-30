<?php

namespace App\Enums;

enum PlatformRole: string
{
    case PlatformAdmin = 'platform_admin';
    case WorkspaceOwner = 'workspace_owner';
    case WorkspaceStaff = 'workspace_staff';
}
