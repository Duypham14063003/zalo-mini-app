<?php

namespace App\Enums;

enum GameStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
}
