<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Closure;
use Kabuto\Ast\ForeachNode;
use Kabuto\Ast\IfBranch;
use Kabuto\Ast\IfNode;
use Kabuto\Ast\Node;

final readonly class ControlBlockParser
{
    public function __construct(
        private ControlDirectiveParser $directiveParser,
    ) {}

    /**
     * @param Closure(ParseBoundary, list<string>): array{list<Node>, ?string} $parseSequence
     */
    public function parse(string $keyword, ParseBoundary $boundary, Closure $parseSequence): Node
    {
        return match ($keyword) {
            'if' => $this->parseIf($boundary, $parseSequence),
            'foreach' => $this->parseForeach($boundary, $parseSequence),
            default => throw ParseException::at('Unexpected @' . $keyword, $this->directiveParser->offset()),
        };
    }

    /**
     * @param Closure(ParseBoundary, list<string>): array{list<Node>, ?string} $parseSequence
     */
    private function parseIf(ParseBoundary $boundary, Closure $parseSequence): IfNode
    {
        [$condition, $location] = $this->directiveParser->parseCondition('if');
        $branches = [];
        $controlBoundary = $boundary->insideControl();

        while (true) {
            [$children, $stop] = $parseSequence($controlBoundary, ['elseif', 'else', 'endif']);
            $branches[] = new IfBranch($condition, $children);

            if ($stop === 'elseif') {
                [$condition] = $this->directiveParser->parseCondition('elseif');
                continue;
            }

            if ($stop === 'endif') {
                $this->directiveParser->consume('endif');

                return new IfNode($branches, null, $location);
            }

            $this->directiveParser->consume('else');
            [$elseChildren, $elseStop] = $parseSequence($controlBoundary, ['elseif', 'else', 'endif']);

            if ($elseStop === 'elseif') {
                throw ParseException::at('Unexpected @elseif after @else', $this->directiveParser->offset());
            }

            if ($elseStop === 'else') {
                throw ParseException::at('Duplicate @else', $this->directiveParser->offset());
            }

            $this->directiveParser->consume('endif');

            return new IfNode($branches, $elseChildren, $location);
        }
    }

    /**
     * @param Closure(ParseBoundary, list<string>): array{list<Node>, ?string} $parseSequence
     */
    private function parseForeach(ParseBoundary $boundary, Closure $parseSequence): ForeachNode
    {
        [$collection, $item, $location] = $this->directiveParser->parseForeach();
        [$children] = $parseSequence($boundary->insideControl(), ['endforeach']);
        $this->directiveParser->consume('endforeach');

        return new ForeachNode($collection, $item, $children, $location);
    }
}
