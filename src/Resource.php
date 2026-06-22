<?php

declare(strict_types=1);

namespace Kabuto;

use Closure;

/**
 * Represents a lazily loaded synchronous resource value.
 */
final class Resource
{
    private bool $loaded = false;

    private mixed $value = null;

    /**
     * Stores the loader used to resolve the resource value.
     */
    public function __construct(
        private readonly Closure $loader,
    ) {}

    /**
     * Returns the resource value, loading it only on the first read.
     */
    public function read(): mixed
    {
        if (!$this->loaded) {
            $this->value = ($this->loader)();
            $this->loaded = true;
        }

        return $this->value;
    }
}
