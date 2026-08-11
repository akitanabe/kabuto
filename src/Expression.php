<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;

/**
 * Describes the limited expression language shared by template surfaces.
 *
 * The source and locations are retained so diagnostics can point back to the
 * original template after the expression has crossed the AST boundary.
 */
final readonly class Expression
{
    /**
     * @param list<string> $filters
     * @param list<SourceLocation|null> $filterLocations
     */
    public function __construct(
        private string $variable,
        private array $filters = [],
        private string $source = '',
        private ?SourceLocation $location = null,
        private array $filterLocations = [],
    ) {}

    /**
     * Returns the variable token, including its leading dollar sign.
     */
    public function variable(): string
    {
        return $this->variable;
    }

    /**
     * Returns the variable identifier without its leading dollar sign.
     */
    public function identifier(): string
    {
        return substr($this->variable, offset: 1);
    }

    /**
     * @return list<string>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * Returns the expression source as it appeared in the attribute.
     */
    public function source(): string
    {
        if ($this->source !== '') {
            return $this->source;
        }

        return $this->variable . ($this->filters === [] ? '' : ' | ' . implode(' | ', $this->filters));
    }

    /**
     * Returns the byte offset where the expression starts in its template.
     */
    public function offset(): int
    {
        return $this->location->offset ?? 0;
    }

    /**
     * Returns the source location of the expression, when parsed from a template.
     */
    public function location(): ?SourceLocation
    {
        return $this->location;
    }

    /**
     * Returns the byte offset where a filter starts in its template.
     */
    public function filterOffset(int $index): int
    {
        return $this->filterLocation($index)->offset ?? $this->offset();
    }

    /**
     * Returns the source location of one filter, when parsed from a template.
     */
    public function filterLocation(int $index): ?SourceLocation
    {
        return $this->filterLocations[$index] ?? $this->location;
    }

    /**
     * @return list<SourceLocation|null>
     */
    public function filterLocations(): array
    {
        return $this->filterLocations;
    }
}
