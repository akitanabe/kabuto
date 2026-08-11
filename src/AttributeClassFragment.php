<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;

final readonly class AttributeClassFragment
{
    public function __construct(
        public mixed $value,
        public AttributeProvenance $provenance,
        public ?SourceLocation $location,
    ) {}
}
