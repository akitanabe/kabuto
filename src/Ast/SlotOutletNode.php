<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class SlotOutletNode implements Node
{
    /**
     * Stores a slot outlet that renders received slot content.
     */
    public function __construct(
        private ?string $name,
    ) {}

    /**
     * Identifies this AST node as a slot outlet.
     */
    public function kind(): string
    {
        return 'slot-outlet';
    }

    /**
     * Returns the requested slot name, or null for the default slot.
     */
    public function name(): ?string
    {
        return $this->name;
    }
}
