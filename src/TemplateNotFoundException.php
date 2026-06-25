<?php

declare(strict_types=1);

namespace Kabuto;

use RuntimeException;

final class TemplateNotFoundException extends RuntimeException
{
    /**
     * Reports that a root-relative template path could not be loaded.
     */
    public static function forPath(string $path): self
    {
        return new self('Template not found: ' . $path);
    }
}
