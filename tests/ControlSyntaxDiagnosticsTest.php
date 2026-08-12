<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\Parser\ParseException;
use Kabuto\RenderContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionFunction;

final class ControlSyntaxDiagnosticsTest extends ControlSyntaxTestCase
{
    #[Test]
    #[TestWith(['@if $ok)yes@endif', 'if directive'])]
    #[TestWith(['@if ($ok)yes', 'missing @endif'])]
    #[TestWith(['@elseif ($ok)yes@endif', 'unexpected @elseif'])]
    #[TestWith(['@elseyes', null])]
    #[TestWith(['@else', 'unexpected @else'])]
    #[TestWith(['@endif', 'unexpected @endif'])]
    #[TestWith(['@endforeach', 'unexpected @endforeach'])]
    #[TestWith(['@if ($ok)a@else b@else c@endif', 'duplicate @else'])]
    #[TestWith(['@if ($ok)a@else b@elseif ($other)c@endif', '@elseif after @else'])]
    #[TestWith(['@foreach ($items)x@endforeach', 'foreach separator'])]
    #[TestWith(['@foreach ($items as)x@endforeach', 'foreach item'])]
    #[TestWith(['@foreach ($items as $item)x@endif', 'expected @endforeach'])]
    public function malformedOrOrphanDirectivesHaveParseDiagnosticParity(string $template, ?string $message): void
    {
        if ($message === null) {
            $this->assertRenderingParity($template, [], $template);

            return;
        }

        [$direct, $compiled] = $this->captureParseFailurePair($template, [
            'ok' => true,
            'other' => true,
            'items' => [],
        ]);

        $this->assertParseDiagnostic($direct, $message);
        $this->assertParseDiagnostic($compiled, $message);
        self::assertSame($direct->getMessage(), $compiled->getMessage());
    }

    #[Test]
    #[TestWith(["head\n  @if \$ok)yes@endif", 'if directive', 7, 2, 3])]
    #[TestWith(["head\n@if (\$ok)yes", 'missing @endif', 17, 2, 13])]
    #[TestWith(["head\n  @elseif (\$ok)yes@endif", 'unexpected @elseif', 7, 2, 3])]
    #[TestWith(["head\n@if (\$ok)a\n@else b\n@else c\n@endif", 'duplicate @else', 24, 4, 1])]
    #[TestWith(["head\n@if (\$ok)a\n@else b\n  @elseif (\$other)c\n@endif", '@elseif after @else', 26, 4, 3])]
    #[TestWith(["head\n@foreach (\$items as \$item)x\n  @endif", 'expected @endforeach', 35, 3, 3])]
    public function representativeControlParseErrorsHaveExactDiagnosticParity(
        string $template,
        string $message,
        int $offset,
        int $line,
        int $byteColumn,
    ): void {
        [$direct, $compiled] = $this->captureParseFailurePair($template, [
            'ok' => true,
            'other' => true,
            'items' => ['x'],
        ]);

        $this->assertExactParseDiagnostic($direct, $message, $offset, $line, $byteColumn);
        $this->assertExactParseDiagnostic($compiled, $message, $offset, $line, $byteColumn);
        self::assertSame($direct->getMessage(), $compiled->getMessage());
    }

    #[Test]
    #[TestWith(['<div>@if ($ok)yes</div>@endif', 'closing tag div'])]
    #[TestWith(['@if ($ok)<div>yes@endif</div>', '@endif'])]
    #[TestWith(['<k-probe>@foreach ($items as $item)x</k-probe>@endforeach', 'closing tag k-probe'])]
    #[TestWith(['@if ($ok)<k-probe>yes@endif</k-probe>', '@endif'])]
    #[TestWith(['<k-probe><k-slot name="named">@if ($ok)x</k-slot>@endif</k-probe>', 'closing tag k-slot'])]
    #[TestWith(['<k-probe>@if ($ok)<k-slot name="named">x</k-slot>@endif</k-probe>', 'conditional named slot'])]
    public function controlBlocksCannotCrossStructuralBoundaries(string $template, string $message): void
    {
        [$direct, $compiled] = $this->captureParseFailurePair($template, ['ok' => true, 'items' => ['x']]);

        $this->assertParseDiagnostic($direct, $message);
        $this->assertParseDiagnostic($compiled, $message);
        self::assertSame($direct->getMessage(), $compiled->getMessage());
    }

    #[Test]
    public function directivesAreOnlyRecognizedInNormalTemplateText(): void
    {
        $template =
            '<!DOCTYPE html><!-- @if ($ok)comment@endif -->'
            . '<div title="@if ($ok)">@iffoo|@elsewhere|@foreachfoo|@endifx|@endforeachx</div>'
            . '<script>@if ($ok)raw@endif</script>'
            . '<style>@foreach ($items as $item)raw@endforeach</style>'
            . '<textarea>@if ($ok)rcdata@endif</textarea>'
            . '<title>@foreach ($items as $item)rcdata@endforeach</title>';
        $expected =
            '<!DOCTYPE html><!-- @if ($ok)comment@endif -->'
            . '<div title="@if ($ok)">@iffoo|@elsewhere|@foreachfoo|@endifx|@endforeachx</div>'
            . '<script>@if ($ok)raw@endif</script>'
            . '<style>@foreach ($items as $item)raw@endforeach</style>'
            . '<textarea>@if ($ok)rcdata@endif</textarea>'
            . '<title>@foreach ($items as $item)rcdata@endforeach</title>';

        $this->assertRenderingParity($template, ['ok' => true, 'items' => ['x']], $expected);
    }

    #[Test]
    public function directivesInsideAnInvalidDoctypeRemainDoctypeSyntaxErrors(): void
    {
        $template = "lead\n<!DOCTYPE @if (\$ok) html>";
        [$direct, $compiled] = $this->captureParseFailurePair($template, ['ok' => true]);

        $this->assertExactParseDiagnostic($direct, 'Expected standard HTML doctype', 5, 2, 1);
        $this->assertExactParseDiagnostic($compiled, 'Expected standard HTML doctype', 5, 2, 1);
        self::assertSame($direct->getMessage(), $compiled->getMessage());
    }

    #[Test]
    public function nonIterableForeachValuesHaveDirectAndCompiledLocationParity(): void
    {
        $template = "before\n@foreach (  \$items as \$item)x@endforeach";
        [$direct, $compiled] = $this->renderFailurePair($template, ['items' => null]);
        $directLocation = $direct->location();

        self::assertSame($direct->getMessage(), $compiled->getMessage());
        self::assertStringContainsString('Foreach value must be iterable', $direct->getMessage());
        self::assertNotNull($directLocation);
        self::assertSame(2, $directLocation->line);
        self::assertSame(13, $directLocation->byteColumn);
        self::assertEquals($directLocation, $compiled->location());

        [$direct, $compiled] = $this->renderFailurePair($template, ['items' => 42]);
        self::assertSame($direct->getMessage(), $compiled->getMessage());
        self::assertEquals($direct->location(), $compiled->location());
    }

    #[Test]
    public function selectedBodyFailuresMatchWhileUnselectedBodiesRemainLazy(): void
    {
        $this->assertRenderingParity(
            '@if ($ok)ok@else{{ $value | missing }}@endif',
            ['ok' => true, 'value' => 'x'],
            'ok',
        );
        [$direct, $compiled] = $this->renderFailurePair("@if (\$ok)ok@else\n{{ \$value | missing }}@endif", [
            'ok' => false,
            'value' => 'x',
        ]);

        self::assertSame($direct->getMessage(), $compiled->getMessage());
        self::assertEquals($direct->location(), $compiled->location());
    }

    #[Test]
    public function generatedClosureKeepsItsPublicSignature(): void
    {
        $compiled = $this->compiledClosure($this->engine(), '@if ($ok)yes@endif');
        $reflection = new ReflectionFunction($compiled);
        $parameters = $reflection->getParameters();

        self::assertTrue($reflection->isStatic());
        self::assertSame(3, $reflection->getNumberOfRequiredParameters());
        self::assertSame(3, $reflection->getNumberOfParameters());
        self::assertSame(
            ['data', 'context', 'renderer'],
            array_map(static fn(\ReflectionParameter $parameter): string => $parameter->getName(), $parameters),
        );
        self::assertSame(
            ['array', RenderContext::class, ComponentRenderer::class],
            array_map(
                static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
                $parameters,
            ),
        );
        self::assertSame('string', (string) $reflection->getReturnType());
    }

    #[Test]
    public function repeatedCompilationProducesStableRenderedOutput(): void
    {
        $engine = $this->engine();
        $template = '@foreach ($items as $item)<span>{{ $item }}</span>@endforeach';
        $data = ['items' => ['one', 'two']];
        $renderer = new ComponentRenderer(new ComponentRegistry());

        $first = $this->renderCompiled($engine, $renderer, $template, $data);
        $second = $this->renderCompiled($engine, $renderer, $template, $data);

        self::assertSame('<span>one</span><span>two</span>', $first);
        self::assertSame($first, $second);
    }

    private function assertParseDiagnostic(ParseException $exception, string $message): void
    {
        self::assertStringContainsString(strtolower($message), strtolower($exception->getMessage()));
        self::assertNotNull($exception->location());
    }
}
