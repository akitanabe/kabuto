<?php

declare(strict_types=1);

namespace Kabuto;

final readonly class RenderScope
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values = [],
        private ?self $parent = null,
    ) {}

    /**
     * Creates the root lexical scope for one render pass.
     *
     * @param array<string, mixed> $values
     */
    public static function root(array $values = []): self
    {
        return new self($values);
    }

    /**
     * Creates an immutable child scope whose values shadow its parent.
     *
     * @param array<string, mixed> $values
     */
    public function child(array $values = []): self
    {
        return new self($values, $this);
    }

    /**
     * Returns a new child scope containing one shadowing value.
     */
    public function with(string $key, mixed $value): self
    {
        return $this->child([$key => $value]);
    }

    /**
     * Resolves a value through the lexical scope chain.
     */
    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $this->parent?->get($key);
    }

    /**
     * Reports whether a binding exists, including bindings whose value is null.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values) || ($this->parent?->has($key) ?? false);
    }
}
