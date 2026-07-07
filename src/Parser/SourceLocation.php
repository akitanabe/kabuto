<?php

declare(strict_types=1);

namespace Kabuto\Parser;

final readonly class SourceLocation
{
    /**
     * Stores a resolved source position for parse and compile diagnostics.
     */
    public function __construct(
        public int $offset,
        public int $line,
        public int $byteColumn,
        public ?string $templateName = null,
    ) {}

    /**
     * Resolves a byte offset into a one-based line and byte column.
     */
    public static function fromOffset(string $source, int $offset, ?string $templateName = null): self
    {
        $resolvedOffset = min(max($offset, 0), strlen($source));
        $beforeOffset = substr($source, offset: 0, length: $resolvedOffset);
        $line = substr_count($beforeOffset, needle: "\n") + 1;
        $lastNewlineOffset = strrpos($beforeOffset, needle: "\n");
        $lineStartOffset = $lastNewlineOffset === false ? 0 : $lastNewlineOffset + 1;

        return new self(
            offset: $offset,
            line: $line,
            byteColumn: strlen($beforeOffset) - $lineStartOffset + 1,
            templateName: $templateName,
        );
    }

    /**
     * Returns a copy with the root-relative template name attached.
     */
    public function withTemplateName(string $templateName): self
    {
        return new self($this->offset, $this->line, $this->byteColumn, $templateName);
    }

    /**
     * Formats this location for exception messages.
     */
    public function format(): string
    {
        if ($this->templateName !== null) {
            return $this->templateName . ':' . $this->line . ':' . $this->byteColumn;
        }

        return 'line ' . $this->line . ', column ' . $this->byteColumn;
    }
}
