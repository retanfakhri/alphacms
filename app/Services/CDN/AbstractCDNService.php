<?php

declare(strict_types=1);

namespace App\Services\CDN;

use App\Contracts\CDNInterface;
use App\Contracts\ClassifiesFailures;
use App\Support\CDN\CDNStats;
use App\Support\CDN\FailureType;

/**
 * Base class for all CDN providers.
 * Handles circuit breaker logic and failure classification.
 */
abstract class AbstractCDNService implements CDNInterface, ClassifiesFailures
{
    protected FailureType $lastFailureType = FailureType::UNKNOWN;

    private ?CDNCircuitBreaker $circuit = null;

    public function __construct()
    {
        // Initialization if needed
    }

    public function enabled(): bool
    {
        return (bool) config("cdn.providers.{$this->name()}.enabled", false);
    }

    public function lastFailureType(): FailureType
    {
        return $this->lastFailureType;
    }

    /**
     * Record a classified failure with optional telemetry and optional
     * circuit breaker impact depending on call context (Operational vs Diagnostic).
     *
     * Finding #108 & #109: Prevent diagnostic checks from polluting metrics
     * or accidentally opening the production circuit breaker.
     */
    protected function failWith(
        FailureType $type,
        bool $recordTelemetry = true,
        bool $affectCircuit = true
    ): void {
        $this->lastFailureType = $type;

        if ($recordTelemetry) {
            CDNStats::recordFailure($this->name());
        }

        if ($affectCircuit && $type->shouldOpenCircuit()) {
            $this->circuit()->recordFailure();
        }
    }

    /**
     * Get the circuit breaker for this provider.
     * Finding #63: Use container for better testability/mocking.
     */
    protected function circuit(): CDNCircuitBreaker
    {
        if ($this->circuit === null) {
            $this->circuit = app(CDNCircuitBreaker::class)->forProvider($this->name());
        }

        return $this->circuit;
    }
}
