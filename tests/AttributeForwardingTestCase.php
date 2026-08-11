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

abstract class AttributeForwardingTestCase extends TestCase
{
    protected function engine(): TemplateEngine
    {
        return new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
    }

    /** @param array<string, mixed> $data */
    protected function assertDirectAndCompiled(string $template, array $data, string $expected): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry());
        $engine = new TemplateEngine($renderer);
        self::assertSame($expected, $engine->render($template, $data));
        self::assertSame($expected, $this->renderCompiled($engine, $renderer, $template, $data));
    }

    /** @param array<string, mixed> $data */
    protected function renderCompiled(
        TemplateEngine $engine,
        ComponentRenderer $renderer,
        string $template,
        array $data,
    ): string {
        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-forwarding-');
        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));
        $compiled = require $path;
        unlink($path);
        self::assertInstanceOf(Closure::class, $compiled);
        $result = $compiled($data, new RenderContext(), $renderer->withTemplateEngine($engine));
        self::assertIsString($result);

        return $result;
    }

    /** @param array<string, mixed> $data */
    protected function assertRenderFailureParity(
        string $template,
        array $data,
        string $message,
        int $line,
        int $column,
    ): void {
        $renderer = new ComponentRenderer(new ComponentRegistry());
        $engine = new TemplateEngine($renderer);
        $this->assertEngineRenderFailureParity(
            $engine,
            $renderer,
            $template,
            $data,
            [
                'message' => $message,
                'line' => $line,
                'column' => $column,
            ],
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array{message: string, line: int, column: int} $expected
     */
    protected function assertEngineRenderFailureParity(
        TemplateEngine $engine,
        ComponentRenderer $renderer,
        string $template,
        array $data,
        array $expected,
    ): void {
        [$direct, $compiled] = $this->renderFailurePair($engine, $renderer, $template, $data);

        self::assertStringContainsString($expected['message'], $direct->getMessage());
        $directLocation = $direct->location();
        $compiledLocation = $compiled->location();
        self::assertNotNull($directLocation);
        self::assertNotNull($compiledLocation);
        self::assertSame($expected['line'], $directLocation->line);
        self::assertSame($expected['column'], $directLocation->byteColumn);
        self::assertEquals($directLocation, $compiledLocation);
        self::assertSame($direct->getMessage(), $compiled->getMessage());
    }

    /**
     * @param array<string, mixed> $data
     * @return array{RenderException, RenderException}
     */
    protected function renderFailurePair(
        TemplateEngine $engine,
        ComponentRenderer $renderer,
        string $template,
        array $data,
    ): array {
        $direct = $this->captureRenderException(static fn(): string => $engine->render($template, $data));
        $compiled = $this->captureRenderException(
            fn(): string => $this->renderCompiled($engine, $renderer, $template, $data),
        );

        return [$direct, $compiled];
    }

    protected function expectParseFailureParity(string $template, string $message): void
    {
        $messages = [];
        try {
            $this->engine()->render($template, ['value' => 'x']);
            self::fail('Expected direct parsing to fail.');
        } catch (ParseException $exception) {
            self::assertStringContainsString($message, strtolower($exception->getMessage()));
            $messages[] = $exception->getMessage();
        }

        try {
            $this->engine()->compilePhp($template);
            self::fail('Expected PHP compilation parsing to fail.');
        } catch (ParseException $exception) {
            self::assertStringContainsString($message, strtolower($exception->getMessage()));
            $messages[] = $exception->getMessage();
        }

        self::assertCount(1, array_unique($messages));
    }

    private function captureRenderException(callable $render): RenderException
    {
        try {
            $render();
            self::fail('Expected rendering to fail.');
        } catch (RenderException $exception) {
            return $exception;
        }
    }
}
