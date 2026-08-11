<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Expression;

final readonly class InterpolationNode implements Node
{
    public function __construct(
        private Expression $expression,
    ) {}

    public function kind(): string
    {
        return 'interpolation';
    }

    public function expression(): Expression
    {
        return $this->expression;
    }
}
