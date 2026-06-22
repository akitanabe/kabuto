<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class ElementNode implements Node
{
    /**
     * Stores a normal HTML element with attributes and children.
     *
     * @param list<AttributeNode> $attributes
     * @param list<Node> $children
     */
    public function __construct(
        private string $name,
        private array $attributes = [],
        private array $children = [],
    ) {}

    /**
     * Identifies this AST node as an HTML element.
     */
    public function kind(): string
    {
        return 'element';
    }

    /**
     * Returns the element tag name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the element attributes.
     *
     * @return list<AttributeNode>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the element child nodes.
     *
     * @return list<Node>
     */
    public function children(): array
    {
        return $this->children;
    }
}
