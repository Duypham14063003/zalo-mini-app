<?php

namespace App\Enums;

enum SpinAttemptStatus: string
{
    case Pending = 'pending';
    case Awarded = 'awarded';
    case Rejected = 'rejected';
}
