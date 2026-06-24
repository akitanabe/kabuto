<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use InvalidArgumentException;

final readonly class ComponentPrefix
{
    /**
     * Validates and stores the configured component tag prefix.
     */
    public function __construct(
        private string $value,
    ) {
        if (preg_match('/^[A-Za-z][A-Za-z0-9:_-]*[-:]$/', $value) !== 1) {
            throw new InvalidArgumentException(
                'Component prefix must be a non-empty readable name prefix ending with "-" or ":".',
            );
        }
    }

    /**
     * Returns whether the parsed tag name belongs to a component.
     */
    public function matches(string $tagName): bool
    {
        return str_starts_with($tagName, $this->value);
    }

    /**
     * Returns the component name with the configured prefix removed.
     */
    public function removeFrom(string $tagName): string
    {
        return substr($tagName, strlen($this->value));
    }

    /**
     * Returns the exact tag name that represents a named slot.
     */
    public function slotTagName(): string
    {
        return $this->value . 'slot';
    }
}
