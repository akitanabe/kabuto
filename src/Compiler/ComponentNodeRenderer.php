<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\PropNode;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;
use Kabuto\Slot;

final class ComponentNodeRenderer
{
    /**
     * Renders a component invocation through the runtime component renderer.
     *
     * @param array<string, mixed> $data
     */
    public function render(
        ComponentNode $node,
        array $data,
        RenderContext $context,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): string {
        return $renderer->component(
            $node->name(),
            $this->props($node->attributes(), $node->props(), $data),
            $this->slot($node->children(), $data, $renderer, $nodeRenderer),
            $this->slots($node->slots(), $data, $renderer, $nodeRenderer),
            $context,
        );
    }

    /**
     * Builds component props from static attributes and dynamic render data.
     *
     * @param list<AttributeNode> $attributes
     * @param list<PropNode> $props
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function props(array $attributes, array $props, array $data): array
    {
        $values = [];

        foreach ($attributes as $attribute) {
            $values[$attribute->name()] = $attribute->value();
        }

        foreach ($props as $prop) {
            $values[$prop->name()] = $data[substr($prop->expression(), offset: 1)] ?? null;
        }

        return $values;
    }

    /**
     * Creates a runtime slot for child nodes.
     *
     * @param list<Node> $children
     * @param array<string, mixed> $data
     */
    private function slot(array $children, array $data, ComponentRenderer $renderer, NodeRenderer $nodeRenderer): ?Slot
    {
        if ($children === []) {
            return null;
        }

        return new Slot(
            static fn(RenderContext $context): string => $nodeRenderer->renderNodes(
                $children,
                $data,
                $context,
                $renderer,
            ),
        );
    }

    /**
     * Creates runtime named slots keyed by slot name.
     *
     * @param array<string, list<Node>> $slots
     * @param array<string, mixed> $data
     * @return array<string, Slot>
     */
    private function slots(array $slots, array $data, ComponentRenderer $renderer, NodeRenderer $nodeRenderer): array
    {
        $values = [];

        foreach ($slots as $name => $children) {
            $slot = $this->slot($children, $data, $renderer, $nodeRenderer);
            if ($slot !== null) {
                $values[$name] = $slot;
            }
        }

        return $values;
    }
}
