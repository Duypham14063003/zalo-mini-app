<?php

namespace App\Enums;

enum GameLaunchStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Invalid = 'invalid';
    case Archived = 'archived';
}
