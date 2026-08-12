<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Closure;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\Parser\ParseException;
use Kabuto\RenderContext;
use Kabuto\RenderException;
use Kabuto\TemplateEngine;
use PHPUnit\Framework\TestCase;

abstract class ControlSyntaxTestCase extends TestCase
{
    protected function engine(): TemplateEngine
    {
        return new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
    }

    /** @param array<string, mixed> $data */
    protected function assertDirectAndCompiled(
        TemplateEngine $engine,
        string $template,
        array $data,
        string $expected,
        ?ComponentRenderer $renderer = null,
    ): void {
        $renderer ??= new ComponentRenderer(new ComponentRegistry());

        self::assertSame($expected, $engine->render($template, $data));
        self::assertSame($expected, $this->renderCompiled($engine, $renderer, $template, $data));
    }

    /** @param array<string, mixed> $data */
    protected function assertRenderingParity(string $template, array $data, string $expected): void
    {
        $this->assertDirectAndCompiled($this->engine(), $template, $data, $expected);
    }

    /** @param array<string, mixed> $data */
    protected function renderCompiled(
        TemplateEngine $engine,
        ComponentRenderer $renderer,
        string $template,
        array $data,
    ): string {
        $compiled = $this->compiledClosure($engine, $template);
        $result = $compiled($data, new RenderContext(), $renderer->withTemplateEngine($engine));
        self::assertIsString($result);

        return $result;
    }

    protected function compiledClosure(TemplateEngine $engine, string $template): Closure
    {
        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-control-');
        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));
        $compiled = require $path;
        unlink($path);
        self::assertInstanceOf(Closure::class, $compiled);

        return $compiled;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{RenderException, RenderException}
     */
    protected function renderFailurePair(string $template, array $data): array
    {
        $renderer = new ComponentRenderer(new ComponentRegistry());
        $engine = new TemplateEngine($renderer);
        $direct = $this->captureRenderException(static fn(): string => $engine->render($template, $data));
        $compiled = $this->captureRenderException(
            fn(): string => $this->renderCompiled($engine, $renderer, $template, $data),
        );

        return [$direct, $compiled];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ParseException, ParseException}
     */
    protected function captureParseFailurePair(string $template, array $data): array
    {
        $engine = $this->engine();
        $direct = $this->captureParseException(static fn(): string => $engine->render($template, $data));
        $compiled = $this->captureParseException(static fn(): string => $engine->compilePhp($template));

        return [$direct, $compiled];
    }

    protected function assertExactParseDiagnostic(
        ParseException $exception,
        string $message,
        int $offset,
        int $line,
        int $byteColumn,
    ): void {
        $location = $exception->location();

        self::assertStringContainsString(strtolower($message), strtolower($exception->getMessage()));
        self::assertNotNull($location);
        self::assertSame($offset, $location->offset);
        self::assertSame($line, $location->line);
        self::assertSame($byteColumn, $location->byteColumn);
    }

    private function captureRenderException(callable $render): RenderException
    {
        try {
            $render();
        } catch (RenderException $exception) {
            return $exception;
        }

        self::fail('Expected rendering to fail.');
    }

    private function captureParseException(callable $parse): ParseException
    {
        try {
            $parse();
        } catch (ParseException $exception) {
            return $exception;
        }

        self::fail('Expected parsing to fail.');
    }
}
