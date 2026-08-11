<?php

declare(strict_types=1);

namespace Kabuto;

use InvalidArgumentException;
use Kabuto\Diagnostics\SourceLocation;

final readonly class AttributeEntry
{
    public string $name;

    public function __construct(
        string $name,
        public mixed $value,
        public AttributeProvenance $provenance,
        public ?SourceLocation $location = null,
    ) {
        if (preg_match('/^[A-Za-z][A-Za-z0-9:_-]*$/', $name) !== 1) {
            $message = 'Invalid attribute name "' . $name . '"';
            throw $location === null
                ? new InvalidArgumentException($message)
                : RenderException::atLocation($message, $location);
        }

        $this->name = strtolower($name);
    }

    public function isDynamic(): bool
    {
        return $this->provenance === AttributeProvenance::Dynamic;
    }
}
