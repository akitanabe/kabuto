<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Diagnostics\SourceLocation;

final readonly class IfNode implements Node
{
    /**
     * @param non-empty-list<IfBranch> $branches
     * @param list<Node>|null $elseChildren
     */
    public function __construct(
        private array $branches,
        private ?array $elseChildren,
        private SourceLocation $location,
    ) {}

    public function kind(): string
    {
        return 'if';
    }

    /** @return non-empty-list<IfBranch> */
    public function branches(): array
    {
        return $this->branches;
    }

    /** @return list<Node>|null */
    public function elseChildren(): ?array
    {
        return $this->elseChildren;
    }

    public function location(): SourceLocation
    {
        return $this->location;
    }
}
