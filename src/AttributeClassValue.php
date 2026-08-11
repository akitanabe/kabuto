<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;
use Stringable;

final class AttributeClassValue
{
    public static function normalize(mixed $class): string
    {
        return match (true) {
            is_array($class) => self::normalizeArray($class),
            $class === null, is_bool($class) => '',
            default => self::string($class),
        };
    }

    public static function normalizeDynamic(
        mixed $class,
        OutputValueStringifier $stringifier,
        SourceLocation $location,
    ): string {
        if ($class instanceof AttributeClassFragments) {
            return trim(implode(' ', array_map(
                static fn(AttributeClassFragment $fragment): string => $fragment->provenance
                    === AttributeProvenance::Dynamic
                        ? self::normalizeDynamic($fragment->value, $stringifier, $fragment->location ?? $location)
                        : self::normalize($fragment->value),
                $class->values,
            )));
        }

        if (is_array($class)) {
            return self::normalizeDynamicArray($class, $stringifier, $location);
        }

        if ($class === null || is_bool($class)) {
            return '';
        }

        return trim($stringifier->stringify($class, $location));
    }

    /** @param array<int|string, mixed> $class */
    private static function normalizeArray(array $class): string
    {
        $classes = array_map(
            static fn(string|int $name, mixed $enabled): string => match (true) {
                is_int($name) => self::string($enabled),
                (bool) $enabled => $name,
                default => '',
            },
            array_keys($class),
            $class,
        );

        return trim(implode(' ', array_filter($classes)));
    }

    /** @param array<int|string, mixed> $class */
    private static function normalizeDynamicArray(
        array $class,
        OutputValueStringifier $stringifier,
        SourceLocation $location,
    ): string {
        $classes = array_map(
            static fn(string|int $name, mixed $enabled): string => match (true) {
                is_int($name) => self::normalizeDynamic($enabled, $stringifier, $location),
                (bool) $enabled => $name,
                default => '',
            },
            array_keys($class),
            $class,
        );

        return trim(implode(' ', array_filter($classes)));
    }

    private static function string(mixed $class): string
    {
        return match (true) {
            is_string($class) => trim($class),
            is_int($class), is_float($class) => (string) $class,
            $class instanceof Stringable => trim($class->__toString()),
            default => '',
        };
    }
}
