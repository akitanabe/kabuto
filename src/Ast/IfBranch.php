<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Expression;

final readonly class IfBranch
{
    /**
     * @param list<Node> $children
     */
    public function __construct(
        private Expression $condition,
        private array $children,
    ) {}

    public function condition(): Expression
    {
        return $this->condition;
    }

    /** @return list<Node> */
    public function children(): array
    {
        return $this->children;
    }
}
