<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

enum SecurityChallengeType: string
{
    case MFA = 'mfa';
    case EmailLink = 'email_link';
    case SecurityQuestion = 'security_question';
    case PasswordReset = 'password_reset';
}
