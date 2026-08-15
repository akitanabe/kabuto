<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

final readonly class CompilerScope
{
    public function __construct(
        public string $scope,
        public string $context,
    ) {}
}
