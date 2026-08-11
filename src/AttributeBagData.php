<?php

declare(strict_types=1);

namespace Kabuto;

use InvalidArgumentException;

final class AttributeBagData
{
    /**
     * @param array<array-key, mixed> $attributes
     * @return array<string, AttributeEntry>
     */
    public static function fromArray(array $attributes): array
    {
        $entries = [];

        foreach ($attributes as $name => $value) {
            self::add($entries, new AttributeEntry((string) $name, $value, AttributeProvenance::Static));
        }

        return $entries;
    }

    /**
     * @param array<array-key, mixed> $entryData
     * @return array<string, AttributeEntry>
     */
    public static function fromEntries(array $entryData): array
    {
        $entries = [];

        foreach ($entryData as $entry) {
            if (!$entry instanceof AttributeEntry) {
                throw new InvalidArgumentException('Attribute entry data must contain only AttributeEntry values.');
            }

            self::add($entries, $entry);
        }

        return $entries;
    }

    /**
     * @param array<string, AttributeEntry> $defaults
     * @param array<string, AttributeEntry> $incoming
     * @return array<string, AttributeEntry>
     */
    public static function merge(array $defaults, array $incoming): array
    {
        $merged = $defaults;

        foreach ($incoming as $name => $entry) {
            if ($name !== 'class') {
                $merged[$name] = $entry;
                continue;
            }

            $current = $merged[$name] ?? null;
            $dynamic = $entry->isDynamic() ? $entry : null;
            if ($dynamic === null && $current?->isDynamic() === true) {
                $dynamic = $current;
            }

            $value = $dynamic === null
                ? trim(
                    AttributeClassValue::normalize($current?->value) . ' '
                        . AttributeClassValue::normalize($entry->value),
                )
                : AttributeClassFragments::combine($current, $entry);

            $merged[$name] = new AttributeEntry(
                'class',
                $value,
                $dynamic === null ? AttributeProvenance::Static : AttributeProvenance::Dynamic,
                $dynamic?->location,
            );
        }

        return $merged;
    }

    /** @param array<string, AttributeEntry> $entries */
    private static function add(array &$entries, AttributeEntry $entry): void
    {
        if (!array_key_exists($entry->name, $entries)) {
            $entries[$entry->name] = $entry;

            return;
        }

        $message = 'Duplicate attribute name "' . $entry->name . '"';
        throw $entry->location === null
            ? new InvalidArgumentException($message)
            : RenderException::atLocation($message, $entry->location);
    }
}
