<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\InterpolationNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\TextNode;

final class TextInterpolationParser
{
    public function __construct(
        private ExpressionParser $expressionParser = new ExpressionParser(),
    ) {}

    /** @return list<Node> */
    public function parse(string $text, int $sourceOffset, string $templateSource): array
    {
        $nodes = [];
        $literal = '';
        $position = 0;
        $length = strlen($text);

        while ($position < $length) {
            if (substr(string: $text, offset: $position, length: 3) === '@{{') {
                $literal .= '{{';
                $position += 3;
                continue;
            }

            if (substr(string: $text, offset: $position, length: 2) !== '{{') {
                $literal .= $text[$position];
                $position++;
                continue;
            }

            if ($literal !== '') {
                $nodes[] = new TextNode($literal);
                $literal = '';
            }

            $openingOffset = $sourceOffset + $position;
            $closing = strpos(haystack: $text, needle: '}}', offset: $position + 2);
            if ($closing === false) {
                throw ParseException::at('Missing closing interpolation delimiter', $openingOffset);
            }

            $expressionOffset = $sourceOffset + $position + 2;
            $expressionSource = substr($text, $position + 2, $closing - $position - 2);
            $nodes[] = new InterpolationNode($this->expressionParser->parse(
                $expressionSource,
                $expressionOffset,
                $templateSource,
            ));
            $position = $closing + 2;
        }

        if ($literal !== '') {
            $nodes[] = new TextNode($literal);
        }

        return $nodes;
    }
}
