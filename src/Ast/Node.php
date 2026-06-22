<?php

declare(strict_types=1);

namespace Kabuto\Ast;

interface Node
{
    /**
     * Returns the stable node kind used by downstream phases.
     */
    public function kind(): string;
}
