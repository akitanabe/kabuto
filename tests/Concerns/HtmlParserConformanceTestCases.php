<?php

declare(strict_types=1);

namespace Kabuto\Tests\Concerns;

use Kabuto\Ast\ElementNode;
use Kabuto\Ast\TextNode;
use Kabuto\Parser\ParseException;
use Kabuto\Parser\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

trait HtmlParserConformanceTestCases
{
    #[Test]
    #[TestWith(['area'])]
    #[TestWith(['base'])]
    #[TestWith(['br'])]
    #[TestWith(['col'])]
    #[TestWith(['embed'])]
    #[TestWith(['hr'])]
    #[TestWith(['img'])]
    #[TestWith(['input'])]
    #[TestWith(['link'])]
    #[TestWith(['meta'])]
    #[TestWith(['source'])]
    #[TestWith(['track'])]
    #[TestWith(['wbr'])]
    public function parserTreatsBareVoidElementsAsChildlessLeaves(string $name): void
    {
        $nodes = new Parser()->parse('<' . $name . '><span>after</span>');

        self::assertCount(2, $nodes);
        self::assertInstanceOf(ElementNode::class, $nodes[0]);
        self::assertSame($name, $nodes[0]->name());
        self::assertSame([], $nodes[0]->children());
        self::assertInstanceOf(ElementNode::class, $nodes[1]);
        self::assertSame('span', $nodes[1]->name());
    }

    #[Test]
    public function parserRepresentsBareStaticAttributesAsBareAttributes(): void
    {
        $nodes = new Parser()->parse('<input required>');

        self::assertCount(1, $nodes);
        self::assertInstanceOf(ElementNode::class, $nodes[0]);
        self::assertSame('required', $nodes[0]->attributes()[0]->name());
        self::assertSame('', $nodes[0]->attributes()[0]->value());
        self::assertTrue($nodes[0]->attributes()[0]->isBare());
    }

    #[Test]
    public function parserPreservesHtmlCommentsAndTopLevelDoctypeAsLiteralNodes(): void
    {
        $nodes = new Parser()->parse('<!dOcTyPe hTmL><!-- top --><main>before<!-- inside -->after</main>');

        self::assertCount(3, $nodes);
        self::assertInstanceOf(TextNode::class, $nodes[0]);
        self::assertSame('<!dOcTyPe hTmL>', $nodes[0]->content());
        self::assertInstanceOf(TextNode::class, $nodes[1]);
        self::assertSame('<!-- top -->', $nodes[1]->content());
        self::assertInstanceOf(ElementNode::class, $nodes[2]);
        self::assertCount(3, $nodes[2]->children());
        self::assertInstanceOf(TextNode::class, $nodes[2]->children()[1]);
        self::assertSame('<!-- inside -->', $nodes[2]->children()[1]->content());
    }

    #[Test]
    #[TestWith(['<!-- unterminated'])]
    #[TestWith(['<!DOCTYPE html'])]
    #[TestWith(['<!DOCTYPE svg>'])]
    #[TestWith(['<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">'])]
    #[TestWith(['<div><!DOCTYPE html></div>'])]
    public function parserRejectsUnterminatedCommentsUnsupportedDoctypesAndNestedDoctypes(string $source): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse($source);
    }

    #[Test]
    #[TestWith(['ScRiPt', 'sCrIpT'])]
    #[TestWith(['StYlE', 'sTyLe'])]
    #[TestWith(['TeXtArEa', 'tExTaReA'])]
    #[TestWith(['TiTlE', 'tItLe'])]
    public function parserKeepsRawTextElementsLiteralAndMatchesClosingTagsCaseInsensitively(
        string $openingName,
        string $closingName,
    ): void {
        $content = '@if ($ready)<k-card><!-- note --><span>literal</span>';
        $nodes = new Parser()->parse('<' . $openingName . '>' . $content . '</' . $closingName . ' >');

        self::assertCount(1, $nodes);
        self::assertInstanceOf(ElementNode::class, $nodes[0]);
        self::assertSame($openingName, $nodes[0]->name());
        self::assertCount(1, $nodes[0]->children());
        self::assertInstanceOf(TextNode::class, $nodes[0]->children()[0]);
        self::assertSame($content, $nodes[0]->children()[0]->content());
    }
}
