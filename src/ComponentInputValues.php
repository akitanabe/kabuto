<?php

declare(strict_types=1);

namespace Kabuto;

final readonly class ComponentInputValues
{
    /** @param array<string, mixed> $props */
    public function __construct(
        public array $props,
        public AttributeBag $attributes,
    ) {}
}
