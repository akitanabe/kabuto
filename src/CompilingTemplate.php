<?php

declare(strict_types=1);

namespace Kabuto;

class CompilingTemplate
{
    /**
     * array{string, string} $contents
     * ?Closure $todo
     *
     */
    public function __construct(
        public readonly string $next,
        public readonly string $pending,
    ) {}
}
