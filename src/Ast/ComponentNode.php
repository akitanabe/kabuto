<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class ComponentNode implements Node
{
    /**
     * Stores a component invocation with props and slot content.
     *
     * @param list<AttributeNode|DynamicAttributeNode> $callerAttributes
     * @param list<PropNode> $props
     * @param list<Node> $children
     * @param array<string, list<Node>> $slots
     */
    public function __construct(
        private string $name,
        private array $callerAttributes = [],
        private array $props = [],
        private array $children = [],
        private array $slots = [],
    ) {}

    /**
     * Identifies this AST node as a component invocation.
     */
    public function kind(): string
    {
        return 'component';
    }

    /**
     * Returns the component name without the configured tag prefix.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns static component attributes.
     *
     * @return list<AttributeNode>
     */
    public function attributes(): array
    {
        return array_values(array_filter(
            $this->callerAttributes,
            static fn(AttributeNode|DynamicAttributeNode $attribute): bool => $attribute instanceof AttributeNode,
        ));
    }

    /**
     * Returns dynamic component props.
     *
     * @return list<PropNode>
     */
    public function props(): array
    {
        return $this->props;
    }

    /** @return list<DynamicAttributeNode> */
    public function dynamicAttributes(): array
    {
        return array_values(array_filter(
            $this->callerAttributes,
            static fn(AttributeNode|DynamicAttributeNode $attribute): bool => (
                $attribute instanceof DynamicAttributeNode
            ),
        ));
    }

    /** @return list<AttributeNode|DynamicAttributeNode> */
    public function callerAttributes(): array
    {
        return $this->callerAttributes;
    }

    /**
     * Returns default slot child nodes.
     *
     * @return list<Node>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * Returns named slot child nodes keyed by slot name.
     *
     * @return array<string, list<Node>>
     */
    public function slots(): array
    {
        return $this->slots;
    }
}
