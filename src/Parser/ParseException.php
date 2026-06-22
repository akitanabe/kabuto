<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use RuntimeException;

final class ParseException extends RuntimeException
{
    /**
     * Creates an exception for unsupported or malformed template syntax.
     */
    public static function at(string $message, int $offset): self
    {
        return new self($message . ' at offset ' . $offset . '.');
    }
}
