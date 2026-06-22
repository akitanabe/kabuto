<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class NamedSlotNode implements Node
{
    /**
     * Stores a parsed named slot before it is attached to a component.
     *
     * @param list<Node> $children
     */
    public function __construct(
        private string $name,
        private array $children,
    ) {}

    /**
     * Identifies this AST node as a named slot.
     */
    public function kind(): string
    {
        return 'slot';
    }

    /**
     * Returns the named slot name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the slot child nodes.
     *
     * @return list<Node>
     */
    public function children(): array
    {
        return $this->children;
    }
}
