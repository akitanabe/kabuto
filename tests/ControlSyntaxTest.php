<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Closure;
use IteratorAggregate;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\ExpressionRuntime;
use Kabuto\FilterRegistry;
use Kabuto\TemplateEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Stringable;
use Traversable;

final class ControlSyntaxTest extends ControlSyntaxTestCase
{
    #[Test]
    #[DataProvider('truthinessValues')]
    public function ifUsesPhpTruthiness(mixed $value, string $expected): void
    {
        $engine = $this->engine();
        $template = '@if ($value)truthy@else{{ $fallback }}@endif';

        $this->assertDirectAndCompiled($engine, $template, ['value' => $value, 'fallback' => 'falsy'], $expected);
    }

    #[Test]
    #[TestWith(['direct'])]
    #[TestWith(['compiled'])]
    public function ifSelectsOnlyTheFirstTruthyBranchAndSupportsNesting(string $mode): void
    {
        $events = [];
        $filters = new FilterRegistry();
        $filters->register('first', $this->observingFilter('first', $events));
        $filters->register('second', $this->observingFilter('second', $events));
        $filters->register('unused', $this->observingFilter('unused', $events));
        $filters->register('inner', $this->observingFilter('inner', $events));
        $renderer = new ComponentRenderer(new ComponentRegistry(), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);
        $template =
            '@if ($first | first)first'
            . '@elseif ($second | second)second:@if ($inner | inner)inner@else{{ $wrong }}@endif'
            . '@elseif ($unused | unused)unused@else{{ $nope }}@endif';
        $data = [
            'first' => false,
            'second' => true,
            'inner' => true,
            'unused' => true,
            'wrong' => 'wrong',
            'nope' => 'nope',
        ];

        $output = $mode === 'direct'
            ? $engine->render($template, $data)
            : $this->renderCompiled($engine, $renderer, $template, $data);

        self::assertSame('second:inner', $output);
        self::assertSame(['first', 'second', 'inner'], $events);
    }

    #[Test]
    #[TestWith(['direct'])]
    #[TestWith(['compiled'])]
    public function foreachEvaluatesAndTraversesItsCollectionExactlyOnceInInputOrder(string $mode): void
    {
        $events = [];
        $filters = new FilterRegistry();
        $filters->register('observe', static function (mixed $value) use (&$events): mixed {
            $events[] = 'evaluate';

            return $value;
        });
        $recordYield = static function (string $value) use (&$events): void {
            $events[] = 'yield:' . $value;
        };
        $collection = new class($recordYield) implements IteratorAggregate {
            public function __construct(
                private readonly Closure $recordYield,
            ) {}

            public int $getIteratorCalls = 0;

            public function getIterator(): Traversable
            {
                $this->getIteratorCalls++;
                foreach (['a', 'b', 'c'] as $value) {
                    ($this->recordYield)($value);
                    yield $value;
                }
            }
        };
        $renderer = new ComponentRenderer(new ComponentRegistry(), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);
        $template = '@foreach ($items | observe as $item)<b>{{ $item }}</b>@endforeach';

        $output = $mode === 'direct'
            ? $engine->render($template, ['items' => $collection])
            : $this->renderCompiled($engine, $renderer, $template, ['items' => $collection]);

        self::assertSame('<b>a</b><b>b</b><b>c</b>', $output);
        self::assertSame(1, $collection->getIteratorCalls);
        self::assertSame(['evaluate', 'yield:a', 'yield:b', 'yield:c'], $events);
    }

    #[Test]
    public function foreachRendersNothingForAnEmptyArray(): void
    {
        $this->assertDirectAndCompiled($this->engine(), '@foreach ($items as $item)x@endforeach', ['items' => []], '');
    }

    #[Test]
    public function nestedLoopShadowingDoesNotLeakAndRestoresOuterBinding(): void
    {
        $first = $this->iterableLabel('one', ['a', 'b']);
        $second = $this->iterableLabel('two', ['c']);
        $template =
            '{{ $item }}|@foreach ($items as $item)'
            . '[{{ $item }}:@foreach ($item as $item){{ $item }}@endforeach={{ $item }}]'
            . '@endforeach|{{ $item }}';

        $this->assertDirectAndCompiled(
            $this->engine(),
            $template,
            ['item' => 'root', 'items' => [$first, $second]],
            'root|[one:ab=one][two:c=two]|root',
        );
    }

    #[Test]
    #[TestWith(['direct'])]
    #[TestWith(['compiled'])]
    public function foreachSeparatorIsAnchoredAfterAnExpressionFilterNamedAs(string $mode): void
    {
        $calls = 0;
        $filters = new FilterRegistry();
        $filters->register('as', static function (mixed $value) use (&$calls): mixed {
            $calls++;

            return $value;
        });
        $renderer = new ComponentRenderer(new ComponentRegistry(), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);
        $template = '@foreach ($items | as as $item){{ $item }}@endforeach';

        $output = $mode === 'direct'
            ? $engine->render($template, ['items' => ['a', 'b']])
            : $this->renderCompiled($engine, $renderer, $template, ['items' => ['a', 'b']]);

        self::assertSame('ab', $output);
        self::assertSame(1, $calls);
    }

    /** @param list<string> $values */
    private function iterableLabel(string $label, array $values): Stringable&IteratorAggregate
    {
        return new class($label, $values) implements Stringable, IteratorAggregate {
            /** @param list<string> $values */
            public function __construct(
                private readonly string $label,
                private readonly array $values,
            ) {}

            public function __toString(): string
            {
                return $this->label;
            }

            public function getIterator(): Traversable
            {
                yield from $this->values;
            }
        };
    }

    /**
     * @param list<string> $events
     */
    private function observingFilter(string $name, array &$events): callable
    {
        return static function (mixed $value) use (&$events, $name): mixed {
            $events[] = $name;

            return $value;
        };
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function truthinessValues(): iterable
    {
        yield 'null' => [null, 'falsy'];
        yield 'false' => [false, 'falsy'];
        yield 'zero integer' => [0, 'falsy'];
        yield 'zero float' => [0.0, 'falsy'];
        yield 'empty string' => ['', 'falsy'];
        yield 'zero string' => ['0', 'falsy'];
        yield 'empty array' => [[], 'falsy'];
        yield 'true' => [true, 'truthy'];
        yield 'positive integer' => [1, 'truthy'];
        yield 'negative integer' => [-1, 'truthy'];
        yield 'non-zero float' => [0.1, 'truthy'];
        yield 'non-empty string' => ['false', 'truthy'];
        yield 'non-empty array' => [[0], 'truthy'];
        yield 'object' => [new \stdClass(), 'truthy'];
    }
}
