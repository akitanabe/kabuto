<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\AttributeBag;
use Kabuto\AttributeEntry;
use Kabuto\AttributeProvenance;
use Kabuto\Diagnostics\SourceLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use stdClass;
use Stringable;

final class AttributeSpreadSecurityTest extends AttributeForwardingTestCase
{
    #[Test]
    public function mergedDynamicClassFailureUsesTheFailingFragmentLocation(): void
    {
        $cause = new RuntimeException('local class conversion failed');
        $local = new class($cause) implements Stringable {
            public function __construct(
                private RuntimeException $cause,
            ) {}

            public function __toString(): string
            {
                throw $this->cause;
            }
        };
        $template = "<div\n :class=\"\$local\" k-attributes=\"\$attributes\"></div>";
        $localOffset = strpos(haystack: $template, needle: '$local');
        self::assertIsInt($localOffset);
        $localLocation = SourceLocation::fromOffset($template, $localOffset);
        $incomingLocation = new SourceLocation(900, 9, 17);
        $incoming = AttributeBag::fromEntries([
            new AttributeEntry('class', 'incoming', AttributeProvenance::Dynamic, $incomingLocation),
        ]);
        $renderer = new \Kabuto\ComponentRenderer(new \Kabuto\ComponentRegistry());
        $engine = new \Kabuto\TemplateEngine($renderer);

        [$direct, $compiled] = $this->renderFailurePair($engine, $renderer, $template, [
            'local' => $local,
            'attributes' => $incoming,
        ]);

        self::assertStringContainsString('Could not convert output to string', $direct->getMessage());
        self::assertSame($direct->getMessage(), $compiled->getMessage());
        self::assertEquals($localLocation, $direct->location());
        self::assertEquals($localLocation, $compiled->location());
        self::assertNotEquals($incomingLocation, $direct->location());
        self::assertSame($cause, $direct->getPrevious());
        self::assertSame($cause, $compiled->getPrevious());
    }

    #[Test]
    public function dynamicClassConversionFailuresUseTheCallerLocationAcrossRenderingPaths(): void
    {
        $location = new SourceLocation(18, 2, 7);
        $cause = new RuntimeException('class conversion failed');
        $throwing = new class($cause) implements Stringable {
            public function __construct(
                private RuntimeException $cause,
            ) {}

            public function __toString(): string
            {
                throw $this->cause;
            }
        };
        $renderer = new \Kabuto\ComponentRenderer(new \Kabuto\ComponentRegistry());
        $engine = new \Kabuto\TemplateEngine($renderer);
        $template = '<div k-attributes="$attributes"></div>';

        foreach ([
            [[$throwing], 'Could not convert output to string', $cause],
            [[new stdClass()], 'Dynamic output must be scalar or Stringable', null],
        ] as [$class, $message, $previous]) {
            $attributes = AttributeBag::fromEntries([
                new AttributeEntry('class', $class, AttributeProvenance::Dynamic, $location),
            ]);
            [$direct, $compiled] = $this->renderFailurePair($engine, $renderer, $template, [
                'attributes' => $attributes,
            ]);

            self::assertStringContainsString($message, $direct->getMessage());
            self::assertSame($direct->getMessage(), $compiled->getMessage());
            self::assertEquals($location, $direct->location());
            self::assertEquals($location, $compiled->location());
            self::assertSame($previous, $direct->getPrevious());
            self::assertSame($previous, $compiled->getPrevious());
        }
    }

    #[Test]
    public function multipleSpreadsAndNormalElementAttrRoutingAreParseErrors(): void
    {
        foreach ([
            '<div k-attributes="$one" k-attributes="$two"></div>',
            '<div :attr:title="$title"></div>',
        ] as $template) {
            $this->expectParseFailureParity(
                $template,
                $template === '<div :attr:title="$title"></div>' ? 'reserved' : 'one attribute spread',
            );
        }

        $this->expectParseFailureParity('<div k-attributes="literal"></div>', 'expression');
        $this->assertDirectAndCompiled(
            '<div data-k-attributes="literal"></div>',
            [],
            '<div data-k-attributes="literal"></div>',
        );
    }

    #[Test]
    public function componentCallerAttributeDuplicatesFailAtTheLaterSourceLocation(): void
    {
        $template = "<k-probe title=\"one\"\n :attr:TITLE=\"\$title\" />";
        $column = strpos(haystack: $template, needle: '$title');
        self::assertIsInt($column);
        $lineStart = strrpos(substr(string: $template, offset: 0, length: $column), needle: "\n");
        self::assertIsInt($lineStart);

        $this->assertRenderFailureParity(
            $template,
            ['title' => 'two'],
            'Duplicate attribute name',
            2,
            $column - $lineStart,
        );
    }

    #[Test]
    public function nonAttributeBagSpreadFailsAtItsExpressionLocationAcrossRenderingPaths(): void
    {
        $this->assertRenderFailureParity(
            "<div\n k-attributes=\"\$value\"></div>",
            ['value' => ['title' => 'not accepted']],
            'AttributeBag',
            2,
            16,
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function unsafeSpreadAttributeProvider(): iterable
    {
        foreach ([
            'href' => 'javascript:alert(1)',
            'href-whitespace' => "java\nscript:x",
            'onclick' => 'run()',
            'style' => 'color:red',
            'srcdoc' => '<p>x</p>',
            'srcset' => 'x 1x',
            'ping' => '/audit',
            'xlink:href' => '#x',
        ] as $case => $value) {
            yield $case => [str_starts_with($case, 'href') ? 'href' : $case, $value];
        }
    }

    #[Test]
    #[DataProvider('unsafeSpreadAttributeProvider')]
    public function dynamicSpreadAttributesCannotBypassOutputPolicy(string $name, string $value): void
    {
        $attributes = AttributeBag::fromEntries([
            new AttributeEntry($name, $value, AttributeProvenance::Dynamic, new SourceLocation(30, 2, 16)),
        ]);

        $this->assertRenderFailureParity(
            "<a\n k-attributes=\"\$attributes\"></a>",
            ['attributes' => $attributes],
            $name === 'href' ? 'Invalid URL' : $name,
            2,
            16,
        );
    }

    #[Test]
    public function staticSpreadAttributesKeepTrustedCompatibility(): void
    {
        $this->assertDirectAndCompiled(
            '<a k-attributes="$attributes"></a>',
            ['attributes' => new AttributeBag([
                'href' => 'javascript:x',
                'onclick' => 'run()',
                'srcset' => 'x 1x',
            ])],
            '<a href="javascript:x" onclick="run()" srcset="x 1x"></a>',
        );
    }
}
