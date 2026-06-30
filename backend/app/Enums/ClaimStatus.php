<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Pending = 'pending';
    case Claimed = 'claimed';
    case Fulfilled = 'fulfilled';
}
