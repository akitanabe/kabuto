<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\ExpressionRuntime;
use Kabuto\FilterRegistry;
use Kabuto\Parser\ParseException;
use Kabuto\RenderContext;
use Kabuto\TemplateEngine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;

final class ContextAwareOutputTest extends TestCase
{
    #[Test]
    public function textInterpolationIsFilteredThenEscapedInHtmlAndRcdataContexts(): void
    {
        $filters = new FilterRegistry();
        $filters->register(
            'wrap',
            static fn(mixed $value): string => '<' . (is_scalar($value) ? (string) $value : '') . '>',
        );
        $renderer = new ComponentRenderer(new ComponentRegistry(), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);
        $template = '<p>Hello {{ $name | wrap }}</p><textarea>{{ $name }}</textarea><title>{{ $name }}</title>';

        $this->assertDirectAndCompiled(
            $engine,
            $template,
            ['name' => 'A&B'],
            '<p>Hello &lt;A&amp;B&gt;</p><textarea>A&amp;B</textarea><title>A&amp;B</title>',
            $renderer,
        );
    }

    #[Test]
    public function textInterpolationRendersNullAsEmptyAndScalarOrStringableValuesAsText(): void
    {
        $engine = $this->engine();
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return '<stringable>';
            }
        };

        $this->assertDirectAndCompiled(
            $engine,
            '{{ $missing }}|{{ $false }}|{{ $number }}|{{ $object }}',
            ['false' => false, 'number' => 12, 'object' => $stringable],
            '||12|&lt;stringable&gt;',
        );
    }

    #[Test]
    public function literalInterpolationDelimiterCompatibilityIsPreserved(): void
    {
        $this->assertDirectAndCompiled(
            $this->engine(),
            '@{{ $name }}|@@{{ $name }}',
            ['name' => 'unused'],
            '{{ $name }}|@{{ $name }}',
        );
    }

    #[Test]
    public function malformedOrUnterminatedInterpolationIsAParseErrorWithLocation(): void
    {
        foreach ([
            "line\n{{ value }}" => 'line 2, column 4',
            "line\n{{ \$value" => 'line 2, column 1',
        ] as $template => $position) {
            $diagnostics = [];
            foreach (['render', 'compilePhp'] as $method) {
                try {
                    $this->engine()->{$method}($template);
                    self::fail('Expected malformed interpolation to be rejected.');
                } catch (ParseException $exception) {
                    self::assertStringContainsString($position, $exception->getMessage());
                    $diagnostics[] = $exception->getMessage();
                }
            }
            self::assertCount(1, array_unique($diagnostics));
        }
    }

    #[Test]
    public function normalElementDynamicAttributesUseHtmlAttributeSemantics(): void
    {
        $template = '<input data-first="1" :title="$title" data-last="2" :disabled="$enabled" :hidden="$empty" :class="$empty" :id="$null">';

        $this->assertDirectAndCompiled(
            $this->engine(),
            $template,
            ['title' => 'A&B"', 'enabled' => true, 'empty' => '', 'null' => null],
            '<input data-first="1" title="A&amp;B&quot;" data-last="2" disabled hidden class="">',
        );
    }

    private function engine(?FilterRegistry $filters = null): TemplateEngine
    {
        return new TemplateEngine(
            new ComponentRenderer(
                new ComponentRegistry(),
                expressionRuntime: new ExpressionRuntime($filters ?? new FilterRegistry()),
            ),
        );
    }

    /** @param array<string, mixed> $data */
    private function assertDirectAndCompiled(
        TemplateEngine $engine,
        string $template,
        array $data,
        string $expected,
        ?ComponentRenderer $runtimeRenderer = null,
    ): void {
        self::assertSame($expected, $engine->render($template, $data));

        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-context-');
        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));
        $compiled = require $path;
        unlink($path);

        self::assertIsCallable($compiled);
        self::assertSame($expected, $compiled(
            $data,
            new RenderContext(),
            $runtimeRenderer ?? new ComponentRenderer(new ComponentRegistry()),
        ));
    }
}
