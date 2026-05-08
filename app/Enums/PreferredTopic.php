<?php

declare(strict_types=1);

namespace App\Enums;

enum PreferredTopic: string
{
    case Politics = 'politics';
    case Economy = 'economy';
    case Sports = 'sports';
    case Entertainment = 'entertainment';
    case Technology = 'technology';
    case Health = 'health';
    case Science = 'science';
    case Culture = 'culture';
    case Local = 'local';
    case World = 'world';
}
