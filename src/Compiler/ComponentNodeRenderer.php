<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\DynamicAttributeNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\PropNode;
use Kabuto\AttributeBag;
use Kabuto\AttributeEntry;
use Kabuto\AttributeProvenance;
use Kabuto\ComponentInputValues;
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
        $inputs = $this->inputs($node, $scope, $renderer);

        return $renderer->component(
            $node->name(),
            new ComponentInvocation(
                $inputs->props,
                $inputs->attributes,
                $this->slot($node->children(), $scope, $renderer, $nodeRenderer),
                $this->slots($node->slots(), $scope, $renderer, $nodeRenderer),
                $context,
            ),
        );
    }

    /**
     * Source order and single evaluation preserve observable filter side effects.
     */
    private function inputs(ComponentNode $node, RenderScope $scope, ComponentRenderer $renderer): ComponentInputValues
    {
        $inputs = [...$node->props(), ...$node->callerAttributes()];
        usort(
            $inputs,
            static fn(
                PropNode|AttributeNode|DynamicAttributeNode $left,
                PropNode|AttributeNode|DynamicAttributeNode $right,
            ): int => $left->position() <=> $right->position(),
        );
        $props = [];
        $entries = [];

        foreach ($inputs as $input) {
            if ($input instanceof PropNode) {
                $props[$input->name()] = $renderer->evaluate($input->expressionData(), $scope);
                continue;
            }

            if ($input instanceof AttributeNode) {
                $entries[] = new AttributeEntry(
                    $input->name(),
                    $input->isBare() ? true : $input->value(),
                    AttributeProvenance::Static,
                    $input->location(),
                );
                continue;
            }

            $location = $input->expression()->location();
            if ($location === null) {
                throw \Kabuto\RenderException::at(
                    'Dynamic attribute has no source location',
                    $input->expression()->offset(),
                );
            }

            $entries[] = new AttributeEntry(
                $input->name(),
                $renderer->evaluate($input->expression(), $scope),
                AttributeProvenance::Dynamic,
                $location,
            );
        }

        return new ComponentInputValues($props, AttributeBag::fromEntries($entries));
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
