<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class AttributeNode
{
    /**
     * Stores a static attribute name and value.
     */
    public function __construct(
        private string $name,
        private string $value,
    ) {}

    /**
     * Returns the attribute name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the static attribute value.
     */
    public function value(): string
    {
        return $this->value;
    }
}
