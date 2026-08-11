<?php

declare(strict_types=1);

namespace Kabuto;

use Closure;

final class FilterRegistry
{
    /**
     * @var array<string, Closure(mixed): mixed>
     */
    private array $filters;

    /**
     * @param array<string, callable(mixed): mixed> $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = [];
        foreach ($filters as $name => $filter) {
            $this->register($name, $filter);
        }
    }

    /**
     * Registers or replaces one explicitly available filter.
     */
    public function register(string $name, callable $filter): self
    {
        $this->filters[$name] = Closure::fromCallable($filter);

        return $this;
    }

    /**
     * Returns whether a filter has been explicitly registered.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->filters);
    }

    /**
     * Returns one registered filter, or null when it is not available.
     *
     * @return Closure(mixed): mixed|null
     */
    public function get(string $name): ?Closure
    {
        return $this->filters[$name] ?? null;
    }
}
