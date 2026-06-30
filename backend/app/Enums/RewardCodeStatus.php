<?php

namespace App\Enums;

enum RewardCodeStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Exhausted = 'exhausted';
}
