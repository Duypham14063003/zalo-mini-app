<?php

namespace App\Enums;

enum GameLaunchChannel: string
{
    case WebPreview = 'web_preview';
    case ZaloMiniApp = 'zalo_mini_app';
}
