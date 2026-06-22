<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\Component;
use Kabuto\Slot;

final class RegistryMessageFactory
{
    /**
     * Creates a message component from registry arguments.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     */
    public function __invoke(array $props, ?Slot $slot, array $slots): Component
    {
        return new RegistryMessageComponent($props, $slot, $slots);
    }
}
