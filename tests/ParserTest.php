<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\ElementNode;
use Kabuto\Ast\TextNode;
use Kabuto\Parser\ParseException;
use Kabuto\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    /**
     * Confirms that text and normal HTML elements are parsed into AST nodes.
     */
    public function testParserBuildsHtmlElementTree(): void
    {
        $nodes = new Parser()->parse('<section class="hero">Hello <strong>world</strong></section>');

        self::assertCount(1, $nodes);
        self::assertInstanceOf(ElementNode::class, $nodes[0]);
        self::assertSame('section', $nodes[0]->name());
        self::assertSame('hero', $nodes[0]->attributes()[0]->value());
        self::assertInstanceOf(TextNode::class, $nodes[0]->children()[0]);
        self::assertSame('Hello ', $nodes[0]->children()[0]->content());
        self::assertInstanceOf(ElementNode::class, $nodes[0]->children()[1]);
        self::assertSame('strong', $nodes[0]->children()[1]->name());
    }

    /**
     * Confirms that component tags expose static attributes, dynamic props, and slots.
     */
    public function testParserBuildsComponentTreeWithPropsAndSlots(): void
    {
        $nodes = new Parser()->parse(
            '<x-card title="Welcome" :count="$count"><x-slot name="header">Head</x-slot><p>Body</p></x-card>',
        );

        self::assertCount(1, $nodes);
        self::assertInstanceOf(ComponentNode::class, $nodes[0]);
        self::assertSame('card', $nodes[0]->name());
        self::assertSame('Welcome', $nodes[0]->attributes()[0]->value());
        self::assertSame('count', $nodes[0]->props()[0]->name());
        self::assertSame('$count', $nodes[0]->props()[0]->expression());
        self::assertArrayHasKey('header', $nodes[0]->slots());
        $header = $nodes[0]->slots()['header'][0];
        self::assertInstanceOf(TextNode::class, $header);
        self::assertSame('Head', $header->content());
        self::assertInstanceOf(ElementNode::class, $nodes[0]->children()[0]);
        self::assertSame('p', $nodes[0]->children()[0]->name());
    }

    /**
     * Confirms that self-closing component tags are accepted.
     */
    public function testParserBuildsSelfClosingComponent(): void
    {
        $nodes = new Parser()->parse('<x-icon name="check" />');

        self::assertCount(1, $nodes);
        self::assertInstanceOf(ComponentNode::class, $nodes[0]);
        self::assertSame('icon', $nodes[0]->name());
        self::assertSame([], $nodes[0]->children());
    }

    /**
     * Confirms that unsupported directive syntax fails explicitly.
     */
    public function testParserRejectsBladeStyleDirectives(): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse('@if ($ok)<p>Ok</p>@endif');
    }

    /**
     * Confirms that arbitrary dynamic PHP expressions are rejected.
     */
    public function testParserRejectsArbitraryDynamicPropExpressions(): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse('<x-card :count="$count + 1" />');
    }

    /**
     * Confirms that malformed nesting fails explicitly.
     */
    public function testParserRejectsMismatchedClosingTags(): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse('<div><span></div>');
    }
}
