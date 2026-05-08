<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\CDN\FailureType;

/**
 * Optional contract for CDN providers that can describe the type of
 * their last failure.
 */
interface ClassifiesFailures
{
    public function lastFailureType(): FailureType;
}
