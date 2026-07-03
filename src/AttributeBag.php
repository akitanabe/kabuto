<?php

declare(strict_types=1);

namespace Kabuto;

use Stringable;

final readonly class AttributeBag
{
    /**
     * Stores normalized component attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private array $attributes = [],
    ) {}

    /**
     * Returns every stored attribute.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Returns one attribute value or the provided default when absent.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Checks whether an attribute is present.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Returns a new bag merged with caller-provided attributes.
     *
     * @param array<string, mixed>|AttributeBag $attributes
     */
    public function merge(array|self $attributes): self
    {
        $incoming = $attributes instanceof self ? $attributes->all() : $attributes;
        $merged = $this->attributes;

        foreach ($incoming as $name => $value) {
            if ($name === 'class') {
                $merged[$name] = trim(
                    $this->normalizeClass($merged[$name] ?? null) . ' ' . $this->normalizeClass($value),
                );

                continue;
            }

            $merged[$name] = $value;
        }

        return new self($merged);
    }

    /**
     * Returns a new bag with additional class names appended.
     *
     * @param string|array<int|string, mixed>|null $class
     */
    public function class(string|array|null $class): self
    {
        return $this->merge(['class' => $class]);
    }

    /**
     * Renders the bag as escaped HTML attributes.
     */
    public function toHtml(): string
    {
        return implode('', array_map($this->renderAttribute(...), array_keys($this->attributes), $this->attributes));
    }

    /**
     * Converts supported class formats into a space-separated string.
     */
    private function normalizeClass(mixed $class): string
    {
        return match (true) {
            is_array($class) => $this->normalizeClassArray($class),
            $class === null, is_bool($class) => '',
            default => $this->stringClass($class),
        };
    }

    /**
     * Renders one attribute or an empty string when omitted.
     */
    private function renderAttribute(string|int $name, mixed $value): string
    {
        $name = (string) $name;
        $value = $name === 'class' ? $this->normalizeClass($value) : $value;

        return match (true) {
            $value === false, $value === null, $value === '' => '',
            $value === true => ' ' . $name,
            default => ' ' . $name . '="' . Escaper::escape($value) . '"',
        };
    }

    /**
     * Converts an array class value into a space-separated string.
     *
     * @param array<int|string, mixed> $class
     */
    private function normalizeClassArray(array $class): string
    {
        $classes = array_map(
            fn(string|int $name, mixed $enabled): string => match (true) {
                is_int($name) => $this->stringClass($enabled),
                (bool) $enabled => $name,
                default => '',
            },
            array_keys($class),
            $class,
        );

        return trim(implode(' ', array_filter($classes)));
    }

    /**
     * Converts scalar or stringable class values into text.
     */
    private function stringClass(mixed $class): string
    {
        return match (true) {
            is_string($class) => trim($class),
            is_int($class), is_float($class) => (string) $class,
            $class instanceof Stringable => trim($class->__toString()),
            default => '',
        };
    }
}
