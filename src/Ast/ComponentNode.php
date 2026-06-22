<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class ComponentNode implements Node
{
    /**
     * Stores a component invocation with props and slot content.
     *
     * @param list<AttributeNode> $attributes
     * @param list<PropNode> $props
     * @param list<Node> $children
     * @param array<string, list<Node>> $slots
     */
    public function __construct(
        private string $name,
        private array $attributes = [],
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
     * Returns the component name without the x- prefix.
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
        return $this->attributes;
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
