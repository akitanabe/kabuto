<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use InvalidArgumentException;
use Kabuto\AttributeBag;
use Kabuto\AttributeEntry;
use Kabuto\AttributeProvenance;
use Kabuto\Component;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\Diagnostics\SourceLocation;
use Kabuto\ExpressionRuntime;
use Kabuto\FilterRegistry;
use Kabuto\RenderContext;
use Kabuto\TemplateEngine;
use Kabuto\TemplateLoader;
use Kabuto\Tests\Fixtures\ForwardingViewComponent;
use PHPUnit\Framework\Attributes\Test;

final class AttributeForwardingTest extends AttributeForwardingTestCase
{
    #[Test]
    public function componentDynamicAttributesAreSeparatedFromPropsAcrossRenderingPaths(): void
    {
        $seen = [];
        /** @param array<string, mixed> $props */
        $factory = static function (array $props, mixed ...$arguments) use (&$seen): Component {
            $attributes = $arguments[3];
            self::assertInstanceOf(AttributeBag::class, $attributes);
            $entry = $attributes->entry('aria-label');
            self::assertNotNull($entry);
            $seen[] = [$props, $attributes->all(), $entry->provenance, $entry->location];
            $label = $props['label'] ?? '';

            return new class(is_string($label) ? $label : '', $attributes) implements Component {
                public function __construct(
                    private string $label,
                    private AttributeBag $attributes,
                ) {}

                public function render(RenderContext $context): string
                {
                    return '<button' . $this->attributes->toHtmlFor('button') . '>' . $this->label . '</button>';
                }
            };
        };
        $renderer = new ComponentRenderer(new ComponentRegistry(['probe' => $factory]));
        $engine = new TemplateEngine($renderer);
        $template = '<k-probe :label="$label" :attr:aria-label="$aria" data-static="yes" />';
        $expected = '<button aria-label="A&amp;B" data-static="yes">Save</button>';

        self::assertSame($expected, $engine->render($template, ['label' => 'Save', 'aria' => 'A&B']));
        self::assertSame($expected, $this->renderCompiled($engine, $renderer, $template, [
            'label' => 'Save',
            'aria' => 'A&B',
        ]));
        self::assertCount(2, $seen);
        self::assertSame(['label' => 'Save'], $seen[0][0]);
        self::assertSame(['aria-label' => 'A&B', 'data-static' => 'yes'], $seen[0][1]);
        self::assertSame(AttributeProvenance::Dynamic, $seen[0][2]);
        $ariaOffset = strpos(haystack: $template, needle: '$aria');
        self::assertIsInt($ariaOffset);
        $expectedLocation = SourceLocation::fromOffset($template, $ariaOffset);
        foreach ($seen as $record) {
            self::assertSame(AttributeProvenance::Dynamic, $record[2]);
            self::assertEquals($expectedLocation, $record[3]);
        }
    }

    #[Test]
    public function componentInputsAreEvaluatedOnceInSourceOrderAcrossRenderingPaths(): void
    {
        $evaluated = [];
        $filters = new FilterRegistry();
        $filters->register('trace', static function (mixed $value) use (&$evaluated): mixed {
            $evaluated[] = $value;

            return $value;
        });
        $factory = static fn(): Component => new class implements Component {
            public function render(RenderContext $context): string
            {
                return 'ok';
            }
        };
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'probe' => $factory,
        ]), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);
        $template = '<k-probe :attr:title="$first | trace" :label="$second | trace" :attr:data-last="$third | trace" />';
        $data = ['first' => 'first', 'second' => 'second', 'third' => 'third'];

        self::assertSame('ok', $engine->render($template, $data));
        self::assertSame(['first', 'second', 'third'], $evaluated);
        $evaluated = [];
        self::assertSame('ok', $this->renderCompiled($engine, $renderer, $template, $data));
        self::assertSame(['first', 'second', 'third'], $evaluated);
    }

    #[Test]
    public function baseAndTemplateOnlyComponentsReceiveReservedAttributeBinding(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'forwarding-view' => ForwardingViewComponent::class,
        ]));
        $engine = new TemplateEngine($renderer, loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        $baseTemplate = '<k-forwarding-view :label="$label" :attr:href="$href" class="caller" />';
        $baseExpected = "<a class=\"view caller\" href=\"/safe\">Go</a>\n";
        self::assertSame($baseExpected, $engine->render($baseTemplate, ['label' => 'Go', 'href' => '/safe']));
        self::assertSame($baseExpected, $this->renderCompiled($engine, $renderer, $baseTemplate, [
            'label' => 'Go',
            'href' => '/safe',
        ]));

        $fallbackTemplate = '<k-forwarding-only :label="$label" :attr:disabled="$disabled" />';
        self::assertSame("<button class=\"fallback\" disabled>Send</button>\n", $engine->render($fallbackTemplate, [
            'label' => 'Send',
            'disabled' => true,
        ]));

        foreach ([
            "<k-forwarding-view\n :attr:href=\"\$href\" />",
            "<k-forwarding-only\n :label=\"\$label\" :attr:href=\"\$href\" />",
        ] as $unsafeTemplate) {
            $offset = strpos(haystack: $unsafeTemplate, needle: '$href');
            self::assertIsInt($offset);
            $location = SourceLocation::fromOffset($unsafeTemplate, $offset);
            $this->assertEngineRenderFailureParity(
                $engine,
                $renderer,
                $unsafeTemplate,
                ['label' => 'Unsafe', 'href' => 'javascript:x'],
                [
                    'message' => 'Invalid URL',
                    'line' => $location->line,
                    'column' => $location->byteColumn,
                ],
            );
        }
    }

    #[Test]
    public function spreadMergeIsPositionIndependentAndUsesCallerOverrideSemantics(): void
    {
        $incoming = AttributeBag::fromEntries([
            new AttributeEntry('class', 'caller', AttributeProvenance::Dynamic, new SourceLocation(0, 1, 1)),
            new AttributeEntry('title', 'caller', AttributeProvenance::Static),
        ]);

        foreach ([
            '<div class="local" title="local" k-attributes="$attributes" data-local="yes"></div>',
            '<div k-attributes="$attributes" class="local" title="local" data-local="yes"></div>',
        ] as $template) {
            $this->assertDirectAndCompiled(
                $template,
                ['attributes' => $incoming],
                '<div class="local caller" title="caller" data-local="yes"></div>',
            );
        }

        $this->assertDirectAndCompiled(
            '<div :class="$local" k-attributes="$attributes"></div>',
            ['local' => ['local'], 'attributes' => $incoming],
            '<div class="local caller" title="caller"></div>',
        );
    }

    #[Test]
    public function customPrefixControlsTheExactSpreadName(): void
    {
        $parser = new \Kabuto\Parser\Parser('ui-');
        $renderer = new ComponentRenderer(new ComponentRegistry());
        $engine = new TemplateEngine($renderer, parser: $parser);
        $template = '<div ui-attributes="$attributes"></div>';
        $attributes = new AttributeBag(['data-id' => '7']);

        self::assertSame('<div data-id="7"></div>', $engine->render($template, ['attributes' => $attributes]));
        self::assertSame('<div data-id="7"></div>', $this->renderCompiled($engine, $renderer, $template, [
            'attributes' => $attributes,
        ]));
        self::assertSame('<div k-attributes="$attributes"></div>', $engine->render('<div k-attributes="$attributes"></div>', [
            'attributes' => $attributes,
        ]));
    }

    #[Test]
    public function reservedAttributePropAndViewDataCollisionsAreExplicit(): void
    {
        foreach (['<k-forwarding-only :attributes="$value" />', '<k-any :attributes="$value" />'] as $template) {
            $this->expectParseFailureParity($template, 'reserved');
        }

        $registry = new ComponentRegistry([
            'probe' => static fn(): Component => new class implements Component {
                public function render(RenderContext $context): string
                {
                    return '';
                }
            },
        ]);
        try {
            $registry->resolve('probe', new \Kabuto\ComponentInvocation([
                'attributes' => 'collision',
            ], new AttributeBag()));
            self::fail('Expected direct reserved prop collision to fail.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('attributes', $exception->getMessage());
        }

        $component = new class(['value' => 'x'], attributes: new AttributeBag()) extends \Kabuto\BaseComponent {
            public function render(RenderContext $context): string
            {
                return $this->view('unused', ['attributes' => 'collision']);
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('attributes');
        $component->render(new RenderContext());
    }
}
