<?php

declare(strict_types=1);

namespace Kabuto;

use RuntimeException;

final readonly class AttributeBag
{
    /** @var array<string, AttributeEntry> */
    private array $entries;

    /**
     * Legacy array callers predate provenance metadata and remain trusted static input;
     * dynamic values must use structured entries so output context is not lost.
     *
     * @param array<array-key, mixed> $attributes
     */
    public function __construct(array $attributes = [], bool $entryData = false)
    {
        $this->entries = $entryData
            ? AttributeBagData::fromEntries($attributes)
            : AttributeBagData::fromArray($attributes);
    }

    /** @param list<AttributeEntry> $entries */
    public static function fromEntries(array $entries): self
    {
        return new self($entries, true);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_map(static fn(AttributeEntry $entry): mixed => $entry->value, $this->entries);
    }

    public function entry(string $name): ?AttributeEntry
    {
        return $this->entries[strtolower($name)] ?? null;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $entry = $this->entry($name);

        if ($entry === null) {
            return $default;
        }

        return $entry->value;
    }

    public function has(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->entries);
    }

    /** @param array<array-key, mixed>|AttributeBag $attributes */
    public function merge(array|self $attributes): self
    {
        $incoming = $attributes instanceof self ? $attributes : new self($attributes);
        return new self(array_values(AttributeBagData::merge($this->entries, $incoming->entries)), true);
    }

    /** @param string|array<int|string, mixed>|null $class */
    public function class(string|array|null $class): self
    {
        return $this->merge(['class' => $class]);
    }

    public function toHtml(): string
    {
        foreach ($this->entries as $entry) {
            if ($entry->isDynamic()) {
                throw new RuntimeException('Dynamic attributes require a target element serializer.');
            }
        }

        return $this->toHtmlFor('div');
    }

    /**
     * The target element is required because output safety depends on its attribute context.
     */
    public function toHtmlFor(string $element, ?OutputRenderer $renderer = null): string
    {
        $renderer ??= new OutputRenderer();
        $html = '';

        foreach ($this->entries as $entry) {
            $value =
                !$entry->isDynamic() && $entry->name === 'class' && !is_bool($entry->value) && $entry->value !== null
                    ? AttributeClassValue::normalize($entry->value)
                    : $entry->value;

            if ($entry->isDynamic()) {
                if ($entry->location === null) {
                    throw new RuntimeException('Dynamic attribute has no source location: ' . $entry->name);
                }

                $html .= $renderer->renderDynamicAttribute($element, $entry->name, $value, $entry->location);
                continue;
            }

            $html .= $renderer->renderStaticAttribute($element, $entry->name, $value);
        }

        return $html;
    }
}
