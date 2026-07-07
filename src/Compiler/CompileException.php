<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Parser\SourceLocation;
use RuntimeException;
use Throwable;

final class CompileException extends RuntimeException
{
    private ?string $reason = null;

    private ?SourceLocation $location = null;

    /**
     * Creates an exception for AST nodes that cannot be compiled.
     */
    public static function unsupportedNode(object $node): self
    {
        return self::withDiagnostic('Unsupported AST node: ' . $node::class);
    }

    /**
     * Returns a copy with source location attached.
     */
    public function withLocation(SourceLocation $location): self
    {
        if ($this->reason === null) {
            return $this;
        }

        return self::withDiagnostic($this->reason, $location, $this);
    }

    /**
     * Returns a copy with the root-relative template name attached.
     */
    public function withTemplateName(string $templateName): self
    {
        if ($this->reason === null || $this->location === null || $this->hasTemplateName()) {
            return $this;
        }

        return self::withDiagnostic($this->reason, $this->location->withTemplateName($templateName), $this);
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
        ?SourceLocation $location = null,
        ?Throwable $previous = null,
    ): self {
        $exception = new self(self::formatDiagnostic($reason, $location), previous: $previous);
        $exception->reason = $reason;
        $exception->location = $location;

        return $exception;
    }

    /**
     * Formats the diagnostic message from its source fields.
     */
    private static function formatDiagnostic(string $reason, ?SourceLocation $location): string
    {
        if ($location !== null) {
            return $reason . ' at ' . $location->format() . '.';
        }

        return $reason;
    }
}
