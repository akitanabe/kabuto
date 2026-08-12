<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Diagnostics\SourceLocation;
use Kabuto\Expression;

final readonly class ControlDirectiveParser
{
    private const string KEYWORD_PATTERN = '/\G@(elseif|endforeach|foreach|endif|else|if)(?![A-Za-z0-9_])/';

    public function __construct(
        private SourceCursor $cursor,
        private ExpressionParser $expressionParser = new ExpressionParser(),
    ) {}

    public function currentKeyword(): ?string
    {
        return $this->keywordAt($this->cursor->offset());
    }

    public function offset(): int
    {
        return $this->cursor->offset();
    }

    /**
     * Finds the next directive boundary outside an interpolation.
     */
    public function nextOffsetBeforeTag(): ?int
    {
        $source = $this->cursor->source();
        $length = strlen($source);
        $position = $this->cursor->offset();

        while ($position < $length && $source[$position] !== '<') {
            if (substr(string: $source, offset: $position, length: 2) === '{{') {
                $closing = strpos(haystack: $source, needle: '}}', offset: $position + 2);
                if ($closing === false) {
                    return null;
                }

                $position = $closing + 2;
                continue;
            }

            if ($source[$position] === '@' && $this->keywordAt($position) !== null) {
                return $position;
            }

            $position++;
        }

        return null;
    }

    /** @return array{Expression, SourceLocation} */
    public function parseCondition(string $keyword): array
    {
        $directiveOffset = $this->cursor->offset();
        $this->cursor->expect('@' . $keyword);
        $this->cursor->skipWhitespace();

        if (!$this->cursor->startsWith('(')) {
            throw ParseException::at('Malformed @' . $keyword . ' directive: expected (', $directiveOffset);
        }

        $this->cursor->expect('(');
        $expressionOffset = $this->cursor->offset();
        $expressionSource = $this->readUntilClosingParenthesis(
            'Malformed @' . $keyword . ' directive: missing )',
            $directiveOffset,
        );
        $expression = $this->expressionParser->parse($expressionSource, $expressionOffset, $this->cursor->source());
        $this->cursor->expect(')');

        return [
            $expression,
            SourceLocation::fromOffset($this->cursor->source(), $directiveOffset),
        ];
    }

    /** @return array{Expression, string, SourceLocation} */
    public function parseForeach(): array
    {
        $directiveOffset = $this->cursor->offset();
        $this->cursor->expect('@foreach');
        $this->cursor->skipWhitespace();

        if (!$this->cursor->startsWith('(')) {
            throw ParseException::at('Malformed @foreach directive: expected (', $directiveOffset);
        }

        $this->cursor->expect('(');
        $headerOffset = $this->cursor->offset();
        $header = $this->readUntilClosingParenthesis('Malformed @foreach directive: missing )', $directiveOffset);
        $matches = [];
        $matched = preg_match('/\s+as\s+\$([A-Za-z_][A-Za-z0-9_]*)\s*\z/', $header, $matches, PREG_OFFSET_CAPTURE);

        if ($matched !== 1) {
            $message = preg_match('/\s+as(?:\s+.*)?\s*\z/', $header) === 1
                ? 'Foreach item variable is required'
                : 'Foreach separator "as $item" is required';

            throw ParseException::at($message, $headerOffset);
        }

        $separatorOffset = $matches[0][1];
        $expression = $this->expressionParser->parse(
            substr(string: $header, offset: 0, length: $separatorOffset),
            $headerOffset,
            $this->cursor->source(),
        );
        $this->cursor->expect(')');

        return [
            $expression,
            $matches[1][0],
            SourceLocation::fromOffset($this->cursor->source(), $directiveOffset),
        ];
    }

    public function consume(string $keyword): void
    {
        $this->cursor->expect('@' . $keyword);
    }

    private function readUntilClosingParenthesis(string $error, int $errorOffset): string
    {
        $start = $this->cursor->offset();
        $end = strpos(haystack: $this->cursor->source(), needle: ')', offset: $start);
        if ($end === false) {
            throw ParseException::at($error, $errorOffset);
        }

        $value = substr(string: $this->cursor->source(), offset: $start, length: $end - $start);
        $this->cursor->expect($value);

        return $value;
    }

    private function keywordAt(int $offset): ?string
    {
        if (preg_match(self::KEYWORD_PATTERN, $this->cursor->source(), $matches, flags: 0, offset: $offset) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
