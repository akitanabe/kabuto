<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Ast\ForeachNode;
use Kabuto\Ast\IfNode;
use Kabuto\Ast\TextNode;
use Kabuto\Parser\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ControlSyntaxParserTest extends TestCase
{
    #[Test]
    public function parserNormalizesNestedControlSyntaxIntoAstData(): void
    {
        $nodes = new Parser()->parse(
            "prefix\n@if ( \$first | active )one@elseif (\$second)"
            . '@foreach ($items | as as $item){{ $item }}@endforeach'
            . '@else.final@endif',
        );

        self::assertCount(2, $nodes);
        self::assertInstanceOf(TextNode::class, $nodes[0]);
        self::assertInstanceOf(IfNode::class, $nodes[1]);
        self::assertSame('if', $nodes[1]->kind());
        self::assertSame(2, $nodes[1]->location()->line);
        self::assertCount(2, $nodes[1]->branches());
        self::assertSame('$first', $nodes[1]->branches()[0]->condition()->variable());
        self::assertSame(['active'], $nodes[1]->branches()[0]->condition()->filters());
        self::assertSame('$second', $nodes[1]->branches()[1]->condition()->variable());
        self::assertInstanceOf(ForeachNode::class, $nodes[1]->branches()[1]->children()[0]);
        $foreach = $nodes[1]->branches()[1]->children()[0];
        self::assertSame('foreach', $foreach->kind());
        self::assertSame('$items', $foreach->collection()->variable());
        self::assertSame(['as'], $foreach->collection()->filters());
        self::assertSame('item', $foreach->item());
        $elseChildren = $nodes[1]->elseChildren();
        self::assertNotNull($elseChildren);
        self::assertInstanceOf(TextNode::class, $elseChildren[0]);
        self::assertSame('.final', $elseChildren[0]->content());
    }
}
