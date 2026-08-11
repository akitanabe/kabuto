<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;
use RuntimeException;
use Throwable;

final class RenderException extends RuntimeException
{
    private ?string $reason = null;

    private ?int $offset = null;

    private ?SourceLocation $location = null;

    /**
     * Creates an exception for a render-time expression failure.
     */
    public static function at(string $message, int $offset): self
    {
        return self::withDiagnostic($message, $offset);
    }

    /**
     * Creates an exception with a location already resolved by the parser.
     */
    public static function atLocation(string $message, SourceLocation $location): self
    {
        return self::withDiagnostic($message, $location->offset, $location);
    }

    /**
     * Resolves the expression offset against its complete template source.
     */
    public function withSource(string $source): self
    {
        if ($this->reason === null || $this->offset === null || $this->location !== null) {
            return $this;
        }

        return self::withDiagnostic(
            $this->reason,
            $this->offset,
            SourceLocation::fromOffset($source, $this->offset),
            $this,
        );
    }

    /**
     * Adds a root-relative template name to an already resolved location.
     */
    public function withTemplateName(string $templateName): self
    {
        if ($this->reason === null || $this->offset === null || $this->location === null || $this->hasTemplateName()) {
            return $this;
        }

        return self::withDiagnostic(
            $this->reason,
            $this->offset,
            $this->location->withTemplateName($templateName),
            $this,
        );
    }

    /**
     * Returns whether this exception has a resolved template location.
     */
    public function hasTemplateName(): bool
    {
        return $this->location?->templateName !== null;
    }

    /**
     * Returns the resolved source location, when one is available.
     */
    public function location(): ?SourceLocation
    {
        return $this->location;
    }

    /**
     * Returns the byte offset that caused the render failure.
     */
    public function offset(): ?int
    {
        return $this->offset;
    }

    /**
     * Creates an exception instance from structured diagnostic fields.
     */
    private static function withDiagnostic(
        string $reason,
        int $offset,
        ?SourceLocation $location = null,
        ?Throwable $previous = null,
    ): self {
        $exception = new self(self::formatDiagnostic($reason, $offset, $location), previous: $previous);
        $exception->reason = $reason;
        $exception->offset = $offset;
        $exception->location = $location;

        return $exception;
    }

    /**
     * Formats a diagnostic from its structured source fields.
     */
    private static function formatDiagnostic(string $reason, int $offset, ?SourceLocation $location): string
    {
        if ($location !== null) {
            return $reason . ' at ' . $location->format() . '.';
        }

        return $reason . ' at offset ' . $offset . '.';
    }
}
