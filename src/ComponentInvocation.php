<?php

declare(strict_types=1);

namespace Kabuto;

final readonly class ComponentInvocation
{
    /**
     * Stores one component call with props, attributes, slots, and context.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     */
    public function __construct(
        private array $props = [],
        private AttributeBag $attributes = new AttributeBag(),
        private ?Slot $slot = null,
        private array $slots = [],
        private ?RenderContext $context = null,
    ) {}

    /**
     * Returns dynamic props prepared for the component.
     *
     * @return array<string, mixed>
     */
    public function props(): array
    {
        return $this->props;
    }

    /**
     * Returns static attributes prepared for the component.
     */
    public function attributes(): AttributeBag
    {
        return $this->attributes;
    }

    /**
     * Returns the default slot prepared for the component.
     */
    public function slot(): ?Slot
    {
        return $this->slot;
    }

    /**
     * Returns named slots prepared for the component.
     *
     * @return array<string, Slot>
     */
    public function slots(): array
    {
        return $this->slots;
    }

    /**
     * Returns the render context explicitly provided to the component call.
     */
    public function context(): ?RenderContext
    {
        return $this->context;
    }
}
