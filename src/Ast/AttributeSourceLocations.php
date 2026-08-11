<?php

declare(strict_types=1);

namespace Kabuto\Ast;

use Kabuto\Diagnostics\SourceLocation;

final readonly class AttributeSourceLocations
{
    public function __construct(
        public SourceLocation $name,
        public SourceLocation $value,
    ) {}
}
