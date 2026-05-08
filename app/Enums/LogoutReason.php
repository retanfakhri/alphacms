<?php

declare(strict_types=1);

namespace App\Enums;

enum LogoutReason: string
{
    case UserRequest = 'user_request';
    case SessionExpired = 'session_expired';
    case SecurityLock = 'security_lock';
    case SecurityRevocation = 'security_revocation';
    case PasswordChanged = 'password_changed';
    case AdminAction = 'admin_action';
    case Unknown = 'unknown';
}
