<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Diagnostics\SourceLocation;
use RuntimeException;
use Throwable;

final class ParseException extends RuntimeException
{
    private ?string $reason = null;

    private ?int $offset = null;

    private ?SourceLocation $location = null;

    /**
     * Creates an exception for unsupported or malformed template syntax.
     */
    public static function at(string $message, int $offset): self
    {
        return self::withDiagnostic($message, $offset);
    }

    /**
     * Returns a copy with line and byte column resolved from the source.
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
     * Returns a copy with the root-relative template name attached.
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
     * Returns whether this exception already identifies a template.
     */
    public function hasTemplateName(): bool
    {
        return $this->location?->templateName !== null;
    }

    /**
     * Returns the resolved source location when one has been attached.
     */
    public function location(): ?SourceLocation
    {
        return $this->location;
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
     * Formats the diagnostic message from its source fields.
     */
    private static function formatDiagnostic(string $reason, int $offset, ?SourceLocation $location): string
    {
        if ($location !== null) {
            return $reason . ' at ' . $location->format() . '.';
        }

        return $reason . ' at offset ' . $offset . '.';
    }
}
