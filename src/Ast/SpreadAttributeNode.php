<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Expression;

final readonly class SpreadAttributeNode
{
    public function __construct(
        private Expression $expression,
    ) {}

    public function expression(): Expression
    {
        return $this->expression;
    }
}
