<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class AttributeNode
{
    public function __construct(
        private string $name,
        private string $value,
        private bool $bare = false,
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

    /**
     * Returns whether the attribute was written without a value.
     */
    public function isBare(): bool
    {
        return $this->bare;
    }
}
