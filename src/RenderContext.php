<?php

declare(strict_types=1);

namespace Kabuto;

final class RenderContext
{
    /**
     * Stores values available during a render pass.
     *
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values = [],
    ) {}

    /**
     * Returns a context value by key, or null when absent.
     */
    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    /**
     * Returns a new context with one value changed.
     */
    public function with(string $key, mixed $value): self
    {
        $next = clone $this;
        $next->values[$key] = $value;

        return $next;
    }
}
