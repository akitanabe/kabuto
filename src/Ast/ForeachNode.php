<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Diagnostics\SourceLocation;
use Kabuto\Expression;

final readonly class ForeachNode implements Node
{
    /**
     * @param list<Node> $children
     */
    public function __construct(
        private Expression $collection,
        private string $item,
        private array $children,
        private SourceLocation $location,
    ) {}

    public function kind(): string
    {
        return 'foreach';
    }

    public function collection(): Expression
    {
        return $this->collection;
    }

    public function item(): string
    {
        return $this->item;
    }

    /** @return list<Node> */
    public function children(): array
    {
        return $this->children;
    }

    public function location(): SourceLocation
    {
        return $this->location;
    }
}
