<?php

declare(strict_types=1);

namespace Kabuto;

use InvalidArgumentException;
use Stringable;

final class Escaper
{
    /**
     * Escapes a value for safe HTML text or attribute output.
     */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars(self::stringify($value), ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8');
    }

    /**
     * Converts scalar or stringable values into text before escaping.
     */
    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return $value->__toString();
        }

        throw new InvalidArgumentException('Escaper only accepts scalar, stringable, or null values.');
    }
}
