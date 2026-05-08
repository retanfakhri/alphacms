<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Banned = 'banned';
    case Suspended = 'suspended';
    case Deleted = 'deleted';
}
