<?php

declare(strict_types=1);

namespace Kabuto;

abstract class BaseComponent implements Component
{
    /**
     * Stores props and slots shared by class-based components.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     */
    public function __construct(
        protected array $props = [],
        protected ?Slot $slot = null,
        protected array $slots = [],
    ) {}

    /**
     * Returns a prop value or the provided default when absent.
     */
    protected function prop(string $name, mixed $default = null): mixed
    {
        return $this->props[$name] ?? $default;
    }

    /**
     * Returns the default slot or a named slot when requested.
     */
    protected function slot(?string $name = null): ?Slot
    {
        if ($name === null) {
            return $this->slot;
        }

        return $this->slots[$name] ?? null;
    }
}
