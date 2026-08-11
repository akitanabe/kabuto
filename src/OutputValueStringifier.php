<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;
use Stringable;
use Throwable;

final class OutputValueStringifier
{
    public function stringify(mixed $value, SourceLocation $location): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        if (!$value instanceof Stringable) {
            throw RenderException::atLocation('Dynamic output must be scalar or Stringable', $location);
        }

        try {
            return (string) $value;
        } catch (Throwable $exception) {
            throw RenderException::atLocation('Could not convert output to string', $location, $exception);
        }
    }
}
