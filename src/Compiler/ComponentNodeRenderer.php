<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\PropNode;
use Kabuto\AttributeBag;
use Kabuto\ComponentInvocation;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;
use Kabuto\RenderScope;
use Kabuto\Slot;

final class ComponentNodeRenderer
{
    /**
     * Renders a component invocation through the runtime component renderer.
     *
     */
    public function render(
        ComponentNode $node,
        RenderScope $scope,
        RenderContext $context,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): string {
        return $renderer->component(
            $node->name(),
            new ComponentInvocation(
                $this->props($node->props(), $scope, $renderer),
                $this->attributes($node->attributes()),
                $this->slot($node->children(), $scope, $renderer, $nodeRenderer),
                $this->slots($node->slots(), $scope, $renderer, $nodeRenderer),
                $context,
            ),
        );
    }

    /**
     * Builds component props from dynamic render data.
     *
     * @param list<PropNode> $props
     * @return array<string, mixed>
     */
    private function props(array $props, RenderScope $scope, ComponentRenderer $renderer): array
    {
        $values = [];

        foreach ($props as $prop) {
            $values[$prop->name()] = $renderer->evaluate($prop->expressionData(), $scope);
        }

        return $values;
    }

    /**
     * Builds a component attribute bag from static attributes.
     *
     * @param list<AttributeNode> $attributes
     */
    private function attributes(array $attributes): AttributeBag
    {
        $values = [];

        foreach ($attributes as $attribute) {
            $values[$attribute->name()] = $attribute->isBare() ? true : $attribute->value();
        }

        return new AttributeBag($values);
    }

    /**
     * Creates a runtime slot for child nodes.
     *
     * @param list<Node> $children
     */
    private function slot(
        array $children,
        RenderScope $scope,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): ?Slot {
        if ($children === []) {
            return null;
        }

        return new Slot(
            static fn(RenderContext $context): string => $nodeRenderer->renderNodes(
                $children,
                $scope,
                $context,
                $renderer,
            ),
        );
    }

    /**
     * Creates runtime named slots keyed by slot name.
     *
     * @param array<string, list<Node>> $slots
     * @return array<string, Slot>
     */
    private function slots(
        array $slots,
        RenderScope $scope,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): array {
        $values = [];

        foreach ($slots as $name => $children) {
            $slot = $this->slot($children, $scope, $renderer, $nodeRenderer);
            if ($slot !== null) {
                $values[$name] = $slot;
            }
        }

        return $values;
    }
}
