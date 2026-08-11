<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Closure;
use Kabuto\Ast\ComponentNode;
use Kabuto\BaseComponent;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\ExpressionRuntime;
use Kabuto\FilterRegistry;
use Kabuto\Parser\ParseException;
use Kabuto\Parser\Parser;
use Kabuto\RenderContext;
use Kabuto\RenderException;
use Kabuto\RenderScope;
use Kabuto\TemplateEngine;
use Kabuto\TemplateLoader;
use Kabuto\Tests\Fixtures\TemplateUserCardComponent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ExpressionRuntimeTest extends TestCase
{
    #[Test]
    public function parserStoresVariableAndFiltersAsExpressionData(): void
    {
        $node = new Parser()->parse('<k-card :name=" $name | first | second " />')[0];
        self::assertInstanceOf(ComponentNode::class, $node);
        $expression = $node->props()[0]->expressionData();

        self::assertSame('$name | first | second', $node->props()[0]->expression());
        self::assertSame('$name', $expression->variable());
        self::assertSame(['first', 'second'], $expression->filters());
        self::assertGreaterThanOrEqual(0, $expression->offset());
        self::assertNotNull($expression->location());
        self::assertSame(1, $expression->location()->line);
        self::assertSame(17, $expression->location()->byteColumn);
        self::assertNotNull($expression->filterLocation(0));
        self::assertSame(25, $expression->filterLocation(0)->byteColumn);
    }

    #[Test]
    public function parserReportsPositionForMalformedExpression(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('at line 2, column 26.');

        new Parser()->parse("<k-card>\n  <k-card :name=\"\$name | \" />\n</k-card>");
    }

    #[Test]
    public function parserRejectsUnsupportedExpressionForms(): void
    {
        $expressions = [
            '$value + 1',
            '$value()',
            '$value.name',
            '$value[0]',
            '42',
            '$value | upper("x")',
            '$value |',
        ];
        $rejected = [];

        foreach ($expressions as $expression) {
            try {
                new Parser()->parse('<k-card :value="' . $expression . '" />');
                self::fail('Expected parser to reject ' . $expression);
            } catch (ParseException) {
                $rejected[] = $expression;
            }
        }

        self::assertSame($expressions, $rejected);
    }

    #[Test]
    public function renderScopeSupportsImmutableParentChildShadowing(): void
    {
        $root = RenderScope::root(['name' => 'root', 'null' => null]);
        $child = $root->child(['name' => 'child']);

        self::assertSame('root', $root->get('name'));
        self::assertSame('child', $child->get('name'));
        self::assertNull($root->get('missing'));
        self::assertNull($root->get('null'));
    }

    #[Test]
    public function filtersRunInDeclarationOrderForDirectAndCompiledPhpRendering(): void
    {
        $filters = new FilterRegistry();
        $filters->register('first', static fn(mixed $value): mixed => is_scalar($value)
            ? (string) $value . '-first'
            : $value);
        $filters->register('second', static fn(mixed $value): mixed => is_scalar($value)
            ? (string) $value . '-second'
            : $value);
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'card' => $this->componentClass(),
        ]), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);
        $template = '<k-card :name=" $name | first | second " />';

        self::assertSame('<p>Alice-first-second</p>', $engine->render($template, ['name' => 'Alice']));

        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-expression-');
        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));
        $compiled = require $path;
        unlink($path);

        self::assertIsCallable($compiled);
        self::assertSame('<p>Alice-first-second</p>', $compiled(
            ['name' => 'Alice'],
            new RenderContext(),
            $renderer->withTemplateEngine($engine),
        ));
    }

    #[Test]
    public function filtersReceiveNullAndUnknownFiltersReportRenderPosition(): void
    {
        $seen = [];
        $filters = new FilterRegistry();
        $filters->register('observe', static function (mixed $value) use (&$seen): mixed {
            $seen[] = $value;

            return $value;
        });
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'card' => $this->componentClass(),
        ]), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);

        self::assertSame('<p>null</p>', $engine->render('<k-card :name="$missing | observe" />'));
        self::assertSame([null], $seen);

        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('Unknown filter "missing" at line 1, column 24.');

        $engine->render('<k-card :name="$name | missing" />', ['name' => 'Alice']);
    }

    #[Test]
    public function unknownFilterLocationMatchesBetweenDirectAndStandaloneCompiledRendering(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'card' => $this->componentClass(),
        ]));
        $engine = new TemplateEngine($renderer);
        $template = "<k-card :name=\"\$name |\n missing\" />";

        $direct = $this->captureRenderException(static fn(): string => $engine->render($template, ['name' => 'Alice']));
        $compiledSource = $engine->compilePhp($template);
        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-expression-');
        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $compiledSource);
        $compiled = require $path;
        unlink($path);
        self::assertIsCallable($compiled);

        $compiledException = $this->captureRenderException(static function () use (
            $compiled,
            $renderer,
            $engine,
        ): string {
            $result = $compiled(['name' => 'Alice'], new RenderContext(), $renderer->withTemplateEngine($engine));
            if (!is_string($result)) {
                throw new UnexpectedValueException('Compiled renderer must return a string.');
            }

            return $result;
        });

        self::assertSame('Unknown filter "missing" at line 2, column 2.', $direct->getMessage());
        self::assertSame($direct->getMessage(), $compiledException->getMessage());
        self::assertSame($direct->location()?->line, $compiledException->location()?->line);
        self::assertSame($direct->location()?->byteColumn, $compiledException->location()?->byteColumn);
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'user-card' => TemplateUserCardComponent::class,
        ]));
        $engine = new TemplateEngine($renderer, loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        try {
            $engine->renderFile('unknown-filter.kbt', ['user' => 'Alice']);
            self::fail('Expected rendering to fail.');
        } catch (RenderException $exception) {
            self::assertStringContainsString('unknown-filter.kbt:1:', $exception->getMessage());
        }
    }

    #[Test]
    public function customFiltersRemainActiveThroughTemplateOnlySlotsAndNestedComponents(): void
    {
        $filters = new FilterRegistry();
        $filters->register('suffix', static fn(mixed $value): mixed => is_scalar($value)
            ? (string) $value . '-filtered'
            : $value);
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'user-card' => TemplateUserCardComponent::class,
        ]), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer, loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));
        $template = '<k-fallback-layout><k-user-card :user="$name | suffix" /></k-fallback-layout>';
        $expected = "<header></header><main><article>Alice-filtered</article></main>\n";

        self::assertSame($expected, $engine->render($template, ['name' => 'Alice']));

        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-expression-');
        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));
        $compiled = require $path;
        unlink($path);
        self::assertIsCallable($compiled);
        self::assertSame($expected, $compiled(
            ['name' => 'Alice'],
            new RenderContext(),
            $renderer->withTemplateEngine($engine),
        ));
    }

    /**
     * Captures the render-time diagnostic while keeping its public contract typed.
     */
    private function captureRenderException(Closure $render): RenderException
    {
        try {
            $render();
        } catch (RenderException $exception) {
            return $exception;
        }

        self::fail('Expected rendering to fail.');
    }

    /**
     * Returns a small component used to observe evaluated props.
     */
    private function componentClass(): string
    {
        return new class extends BaseComponent {
            public mixed $name = null;

            public function render(RenderContext $context): string
            {
                $name = $this->prop('name');

                if ($name === null) {
                    return '<p>null</p>';
                }

                return '<p>' . (is_scalar($name) ? (string) $name : '') . '</p>';
            }
        }::class;
    }
}
