<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use InvalidArgumentException;
use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\ElementNode;
use Kabuto\Ast\SlotOutletNode;
use Kabuto\Ast\TextNode;
use Kabuto\Parser\ParseException;
use Kabuto\Parser\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    /**
     * Confirms that text and normal HTML elements are parsed into AST nodes.
     */
    #[Test]
    public function parserBuildsHtmlElementTree(): void
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
    #[Test]
    public function parserBuildsComponentTreeWithPropsAndSlots(): void
    {
        $nodes = new Parser()->parse(
            '<k-card title="Welcome" :count="$count"><k-slot name="header">Head</k-slot><p>Body</p></k-card>',
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

        $outlets = new Parser()->parse('<k-slot /><k-slot name="header" />');

        self::assertCount(2, $outlets);
        self::assertInstanceOf(SlotOutletNode::class, $outlets[0]);
        self::assertNull($outlets[0]->name());
        self::assertInstanceOf(SlotOutletNode::class, $outlets[1]);
        self::assertSame('header', $outlets[1]->name());

        $componentWithOutlets = new Parser()->parse(
            '<k-card><k-slot /><k-slot name="header" /><k-slot name="footer">Foot</k-slot></k-card>',
        );

        self::assertCount(1, $componentWithOutlets);
        self::assertInstanceOf(ComponentNode::class, $componentWithOutlets[0]);
        self::assertCount(2, $componentWithOutlets[0]->children());
        self::assertInstanceOf(SlotOutletNode::class, $componentWithOutlets[0]->children()[0]);
        self::assertNull($componentWithOutlets[0]->children()[0]->name());
        self::assertInstanceOf(SlotOutletNode::class, $componentWithOutlets[0]->children()[1]);
        self::assertSame('header', $componentWithOutlets[0]->children()[1]->name());
        self::assertArrayHasKey('footer', $componentWithOutlets[0]->slots());
    }

    /**
     * Confirms that self-closing component tags are accepted.
     */
    #[Test]
    public function parserBuildsSelfClosingComponentAndProviderNames(): void
    {
        $nodes = new Parser()->parse('<k-icon name="check" /><k-provider /><k-store:provide />');

        self::assertCount(3, $nodes);
        self::assertInstanceOf(ComponentNode::class, $nodes[0]);
        self::assertSame('icon', $nodes[0]->name());
        self::assertSame([], $nodes[0]->children());
        self::assertInstanceOf(ComponentNode::class, $nodes[1]);
        self::assertSame('provider', $nodes[1]->name());
        self::assertInstanceOf(ComponentNode::class, $nodes[2]);
        self::assertSame('store:provide', $nodes[2]->name());
    }

    /**
     * Confirms that unsupported directive syntax fails explicitly.
     */
    #[Test]
    public function parserRejectsBladeStyleDirectives(): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse('@if ($ok)<p>Ok</p>@endif');
    }

    /**
     * Confirms that arbitrary dynamic PHP expressions are rejected.
     */
    #[Test]
    public function parserRejectsArbitraryDynamicPropExpressions(): void
    {
        $sources = [
            '<k-card :count="$count + 1" />',
            '<k-slot :name="$name" />',
            '<k-slot class="hidden" />',
            '<k-slot name="header" class="hidden" />',
        ];
        $rejected = [];

        foreach ($sources as $source) {
            try {
                new Parser()->parse($source);
                self::fail('Expected parser to reject ' . $source);
            } catch (ParseException) {
                $rejected[] = $source;
            }
        }

        self::assertSame($sources, $rejected);
    }

    /**
     * Confirms that malformed nesting fails explicitly.
     */
    #[Test]
    public function parserRejectsMismatchedClosingTags(): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse('<div><span></div>');
    }

    /**
     * Confirms that legacy x-prefixed custom elements are not components by default.
     */
    #[Test]
    public function parserTreatsLegacyXPrefixedTagsAsHtmlByDefaultUnlessConfigured(): void
    {
        $nodes = new Parser()->parse('<x-card><x-slot name="header">Head</x-slot></x-card>');

        self::assertCount(1, $nodes);
        self::assertInstanceOf(ElementNode::class, $nodes[0]);
        self::assertSame('x-card', $nodes[0]->name());
        self::assertInstanceOf(ElementNode::class, $nodes[0]->children()[0]);
        self::assertSame('x-slot', $nodes[0]->children()[0]->name());
        $configuredNodes = new Parser(componentPrefix: 'x-')->parse(
            '<x-card><x-slot name="header">Head</x-slot></x-card>',
        );

        self::assertCount(1, $configuredNodes);
        self::assertInstanceOf(ComponentNode::class, $configuredNodes[0]);
        self::assertSame('card', $configuredNodes[0]->name());
        self::assertArrayHasKey('header', $configuredNodes[0]->slots());
    }

    /**
     * Confirms that named slots must match the configured prefix exactly.
     */
    #[Test]
    public function parserOnlyAcceptsNamedSlotsForTheConfiguredPrefix(): void
    {
        $nodes = new Parser(componentPrefix: 'ui:')->parse(
            '<ui:card><ui:slot name="header">Head</ui:slot><k-slot name="footer">Foot</k-slot></ui:card>',
        );

        self::assertCount(1, $nodes);
        self::assertInstanceOf(ComponentNode::class, $nodes[0]);
        self::assertArrayHasKey('header', $nodes[0]->slots());
        self::assertCount(1, $nodes[0]->children());
        self::assertInstanceOf(ElementNode::class, $nodes[0]->children()[0]);
        self::assertSame('k-slot', $nodes[0]->children()[0]->name());
    }

    /**
     * Confirms that component prefixes are validated before parsing starts.
     */
    #[Test]
    #[TestWith([''])]
    #[TestWith(['1-'])]
    #[TestWith(['k'])]
    #[TestWith(['k_'])]
    #[TestWith(['k.'])]
    public function parserRejectsInvalidComponentPrefix(string $componentPrefix): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Parser(componentPrefix: $componentPrefix);
    }

    /**
     * Confirms that a tag containing only the component prefix is invalid.
     */
    #[Test]
    public function parserRejectsEmptyComponentName(): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse('<k-></k->');
    }
}
