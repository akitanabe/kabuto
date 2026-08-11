<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Expression;

final readonly class DynamicAttributeNode
{
    public function __construct(
        private string $name,
        private Expression $expression,
        private int $position = PHP_INT_MAX,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function expression(): Expression
    {
        return $this->expression;
    }

    public function position(): int
    {
        return $this->position;
    }
}
