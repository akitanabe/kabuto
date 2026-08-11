<?php

declare(strict_types=1);

namespace Kabuto;

final readonly class AttributeClassFragments
{
    /** @param list<AttributeClassFragment> $values */
    private function __construct(
        public array $values,
    ) {}

    public static function combine(?AttributeEntry $defaults, AttributeEntry $incoming): self
    {
        return new self([...self::valuesOf($defaults), ...self::valuesOf($incoming)]);
    }

    /** @return list<AttributeClassFragment> */
    private static function valuesOf(?AttributeEntry $entry): array
    {
        if ($entry === null) {
            return [];
        }

        if ($entry->value instanceof self) {
            return $entry->value->values;
        }

        return [new AttributeClassFragment($entry->value, $entry->provenance, $entry->location)];
    }
}
