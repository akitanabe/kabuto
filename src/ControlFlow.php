<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;
use Traversable;

final class ControlFlow
{
    public static function condition(mixed $value): bool
    {
        return (bool) $value;
    }

    /** @return array<array-key, mixed>|Traversable<array-key, mixed> */
    public static function iterable(mixed $value, ?SourceLocation $location, int $fallbackOffset): array|Traversable
    {
        if (is_array($value) || $value instanceof Traversable) {
            return $value;
        }

        $message = 'Foreach value must be iterable';

        throw $location === null
            ? RenderException::at($message, $fallbackOffset)
            : RenderException::atLocation($message, $location);
    }
}
