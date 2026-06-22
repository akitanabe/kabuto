<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class PropNode
{
    /**
     * Stores a dynamic prop name and variable expression.
     */
    public function __construct(
        private string $name,
        private string $expression,
    ) {}

    /**
     * Returns the prop name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the dynamic variable expression.
     */
    public function expression(): string
    {
        return $this->expression;
    }
}
