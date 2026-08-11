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
     * @param list<DynamicAttributeNode> $dynamicAttributes
     */
    public function __construct(
        private string $name,
        private array $attributes = [],
        private array $children = [],
        private array $dynamicAttributes = [],
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

    /** @return list<DynamicAttributeNode> */
    public function dynamicAttributes(): array
    {
        return $this->dynamicAttributes;
    }

    /** @return list<AttributeNode|DynamicAttributeNode> */
    public function outputAttributes(): array
    {
        $attributes = [...$this->attributes, ...$this->dynamicAttributes];
        usort(
            $attributes,
            static fn(
                AttributeNode|DynamicAttributeNode $left,
                AttributeNode|DynamicAttributeNode $right,
            ): int => $left->position() <=> $right->position(),
        );

        return $attributes;
    }
}
