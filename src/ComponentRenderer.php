<?php

declare(strict_types=1);

namespace Kabuto;

final class ComponentRenderer
{
    /**
     * Stores the registry used for explicit component name resolution.
     */
    public function __construct(
        private ComponentRegistry $registry,
    ) {}

    /**
     * Resolves a component by name and renders it synchronously.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     */
    public function component(
        string $name,
        array $props = [],
        ?Slot $slot = null,
        array $slots = [],
        ?RenderContext $context = null,
    ): string {
        return $this->registry->resolve($name, $props, $slot, $slots)->render($context ?? new RenderContext());
    }
}
