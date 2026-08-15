<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

final class PhpNameAllocator
{
    private int $nextIdentifier = 0;

    public function next(string $purpose): string
    {
        $this->nextIdentifier++;

        return '$__kabuto' . ucfirst($purpose) . $this->nextIdentifier;
    }
}
