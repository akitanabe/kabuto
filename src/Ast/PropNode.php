<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Expression;
use Kabuto\Parser\ExpressionParser;

final readonly class PropNode
{
    /**
     * Stores a dynamic prop name and variable expression.
     */
    public function __construct(
        private string $name,
        Expression|string $expression,
        private int $position = PHP_INT_MAX,
    ) {
        $this->expressionData = $expression instanceof Expression
            ? $expression
            : new ExpressionParser()->parse($expression);
    }

    private Expression $expressionData;

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
        return $this->expressionData->source();
    }

    /**
     * Returns the parsed immutable expression data.
     */
    public function expressionData(): Expression
    {
        return $this->expressionData;
    }

    public function position(): int
    {
        return $this->position;
    }
}
