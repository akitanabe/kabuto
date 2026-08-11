<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Diagnostics\SourceLocation;
use Kabuto\Expression;

final class ExpressionParser
{
    /**
     * Parses one limited template expression and retains its source locations.
     */
    public function parse(string $source, int $sourceOffset = 0, ?string $templateSource = null): Expression
    {
        $position = $this->skipWhitespace($source, 0);
        $start = $position;

        $variable = $this->parseVariable($source, $position, $sourceOffset);
        [$filters, $filterLocations] = $this->parseFilters($source, $position, $sourceOffset, $templateSource);
        $location = $this->location($templateSource, $sourceOffset + $start);

        return new Expression('$' . $variable, $filters, rtrim(substr($source, $start)), $location, $filterLocations);
    }

    /**
     * Parses the required variable token.
     */
    private function parseVariable(string $source, int &$position, int $sourceOffset): string
    {
        if ($position >= strlen($source) || $source[$position] !== '$') {
            throw ParseException::at('Expected variable expression', $sourceOffset + $position);
        }

        $position++;
        $variableStart = $position;
        $variable = $this->readIdentifier($source, $position);
        if ($variable === null) {
            throw ParseException::at('Expected variable identifier', $sourceOffset + $variableStart);
        }

        return $variable;
    }

    /**
     * Parses the optional left-to-right filter chain.
     *
     * @return array{0: list<string>, 1: list<SourceLocation|null>}
     */
    private function parseFilters(string $source, int &$position, int $sourceOffset, ?string $templateSource): array
    {
        $filters = [];
        $filterLocations = [];
        $length = strlen($source);

        while (true) {
            $position = $this->skipWhitespace($source, $position);
            if ($position >= $length) {
                break;
            }

            if ($source[$position] !== '|') {
                throw ParseException::at('Unexpected token in expression', $sourceOffset + $position);
            }

            $position++;
            $position = $this->skipWhitespace($source, $position);
            $filterOffset = $position;
            $filter = $this->readIdentifier($source, $position);
            if ($filter === null) {
                throw ParseException::at('Expected filter name', $sourceOffset + $filterOffset);
            }

            $filters[] = $filter;
            $filterLocations[] = $this->location($templateSource, $sourceOffset + $filterOffset);
        }

        return [$filters, $filterLocations];
    }

    /**
     * Resolves a location against the complete template when available.
     */
    private function location(?string $templateSource, int $offset): SourceLocation
    {
        if ($templateSource !== null) {
            return SourceLocation::fromOffset($templateSource, $offset);
        }

        return new SourceLocation($offset, 1, $offset + 1);
    }

    /**
     * Skips the whitespace admitted at expression boundaries and separators.
     */
    private function skipWhitespace(string $source, int $position): int
    {
        $length = strlen($source);
        while ($position < $length && ctype_space($source[$position])) {
            $position++;
        }

        return $position;
    }

    /**
     * Reads one grammar identifier at the current byte offset.
     */
    private function readIdentifier(string $source, int &$position): ?string
    {
        if (preg_match('/\G[A-Za-z_][A-Za-z0-9_]*/', $source, $matches, flags: 0, offset: $position) !== 1) {
            return null;
        }

        $position += strlen($matches[0]);

        return $matches[0];
    }
}
