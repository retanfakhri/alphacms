<?php

declare(strict_types=1);

namespace App\Enums;

enum LoginType: string
{
    case Manual = 'manual';
    case Social = 'social';
    case Api = 'api';
    case Token = 'token';
}
