<?php

declare(strict_types=1);

namespace Kabuto;

final class ComponentRenderer
{
    /**
     * Stores the registry used for explicit component name resolution.
     *
     * @param array<string, Slot> $slots
     */
    public function __construct(
        private ComponentRegistry $registry,
        private ?TemplateEngine $templateEngine = null,
        private ?Slot $slot = null,
        private array $slots = [],
    ) {}

    /**
     * Returns a renderer clone bound to the current template engine.
     */
    public function withTemplateEngine(TemplateEngine $templateEngine): self
    {
        $renderer = clone $this;
        $renderer->templateEngine = $templateEngine;

        return $renderer;
    }

    /**
     * Returns a renderer clone bound to the slots visible to slot outlets.
     *
     * @param array<string, Slot> $slots
     */
    public function withSlots(?Slot $slot, array $slots): self
    {
        $renderer = clone $this;
        $renderer->slot = $slot;
        $renderer->slots = $slots;

        return $renderer;
    }

    /**
     * Renders the default or named slot currently visible to a template outlet.
     */
    public function slotOutlet(?string $name, RenderContext $context): string
    {
        $slot = $name === null ? $this->slot : $this->slots[$name] ?? null;

        return $slot?->render($context) ?? '';
    }

    /**
     * Resolves a component by name and renders it synchronously.
     *
     * @param array<string, mixed>|ComponentInvocation $props
     * @param array<string, Slot> $slots
     */
    public function component(
        string $name,
        array|ComponentInvocation $props = [],
        ?Slot $slot = null,
        array $slots = [],
        ?RenderContext $context = null,
    ): string {
        $invocation = $props instanceof ComponentInvocation
            ? $props
            : new ComponentInvocation($props, slot: $slot, slots: $slots, context: $context);
        $component = $this->registry->resolve($name, $invocation, templateEngine: $this->templateEngine);

        return $component->render($invocation->context() ?? new RenderContext());
    }
}
