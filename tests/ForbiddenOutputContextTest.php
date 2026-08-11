<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\Diagnostics\SourceLocation;
use Kabuto\OutputRenderer;
use Kabuto\Parser\ParseException;
use Kabuto\RenderContext;
use Kabuto\RenderException;
use Kabuto\TemplateEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stringable;
use UnexpectedValueException;

final class ForbiddenOutputContextTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function forbiddenContextProvider(): iterable
    {
        yield 'script text' => ['<script>{{ $value }}</script>', 'Dynamic output is forbidden in script content'];
        yield 'style text' => ['<style>{{ $value }}</style>', 'Dynamic output is forbidden in style content'];
        yield 'event handler' => ['<button :OnClick="$value">x</button>', 'Dynamic attribute OnClick is forbidden'];
        yield 'style attribute' => ['<div :style="$value"></div>', 'Dynamic attribute style is forbidden'];
        yield 'srcdoc attribute' => ['<iframe :srcdoc="$value"></iframe>', 'Dynamic attribute srcdoc is forbidden'];
        yield 'srcset attribute' => ['<img :srcset="$value">', 'Dynamic attribute srcset is unsupported'];
        yield 'ping attribute' => ['<a :ping="$value"></a>', 'Dynamic attribute ping is unsupported'];
        yield 'xlink href attribute' => ['<a :xlink:href="$value"></a>', 'Dynamic attribute xlink:href is unsupported'];
    }

    #[Test]
    #[DataProvider('forbiddenContextProvider')]
    public function contextKnownForbiddenAndUnsupportedOutputsFailDuringParsing(string $template, string $message): void
    {
        $diagnostics = [];
        foreach (['render', 'compilePhp'] as $method) {
            try {
                $this->engine()->{$method}($template);
                self::fail('Expected context to be rejected.');
            } catch (ParseException $exception) {
                self::assertStringContainsString($message, $exception->getMessage());
                self::assertNotNull($exception->location());
                $diagnostics[] = $exception->getMessage();
            }
        }
        self::assertCount(1, array_unique($diagnostics));
    }

    #[Test]
    public function invalidTextOutputTypeFailsWithDirectAndCompiledLocationParity(): void
    {
        $template = "before\n{{ \$value }}";
        $data = ['value' => []];
        $engine = $this->engine();
        $direct = $this->captureRenderException(static fn(): string => $engine->render($template, $data));
        $compiled = $this->compiledRenderer($engine, $template);
        $compiledException = $this->captureRenderException(static function () use ($compiled, $data): string {
            $result = $compiled($data, new RenderContext(), new ComponentRenderer(new ComponentRegistry()));
            if (!is_string($result)) {
                throw new UnexpectedValueException('Compiled renderer must return a string.');
            }

            return $result;
        });

        self::assertStringContainsString('scalar or Stringable', $direct->getMessage());
        $location = $direct->location();
        self::assertNotNull($location);
        self::assertSame(2, $location->line);
        self::assertSame(4, $location->byteColumn);
        self::assertSame($direct->getMessage(), $compiledException->getMessage());
    }

    #[Test]
    public function publicAttributeSinkRejectsForbiddenAndUnsupportedContexts(): void
    {
        $renderer = new OutputRenderer();
        $location = new SourceLocation(offset: 8, line: 2, byteColumn: 5);

        foreach (['onclick', 'style', 'srcdoc', 'srcset', 'ping', 'xlink:href'] as $name) {
            try {
                $renderer->renderDynamicAttribute('a', $name, null, $location);
                self::fail('Expected the public sink to reject ' . $name . '.');
            } catch (RenderException $exception) {
                self::assertStringContainsString('Dynamic attribute ' . $name, $exception->getMessage());
                self::assertSame($location, $exception->location());
            }
        }
    }

    #[Test]
    public function attributeOutputFailuresPreserveTypeLocationAndStringableCauseAcrossRenderers(): void
    {
        $engine = $this->engine();
        $arrayTemplate = "<div\n :title=\"\$value\"></div>";
        $arrayData = ['value' => []];
        $directArray = $this->captureRenderException(
            static fn(): string => $engine->render($arrayTemplate, $arrayData),
        );
        $compiledArray = $this->compiledRenderer($engine, $arrayTemplate);
        $compiledArrayException = $this->captureRenderException(static function () use (
            $compiledArray,
            $arrayData,
        ): string {
            $result = $compiledArray($arrayData, new RenderContext(), new ComponentRenderer(new ComponentRegistry()));

            return is_string($result) ? $result : throw new UnexpectedValueException('Expected string output.');
        });
        self::assertStringContainsString('scalar or Stringable', $directArray->getMessage());
        $arrayLocation = $directArray->location();
        self::assertNotNull($arrayLocation);
        self::assertSame(2, $arrayLocation->line);
        self::assertSame(10, $arrayLocation->byteColumn);
        self::assertSame($directArray->getMessage(), $compiledArrayException->getMessage());

        $causeMessage = 'string conversion failed';
        $throwing = new class($causeMessage) implements Stringable {
            public function __construct(
                private string $message,
            ) {}

            public function __toString(): string
            {
                throw new RuntimeException($this->message);
            }
        };
        $textTemplate = "before\n{{ \$value }}";
        $textData = ['value' => $throwing];
        $directText = $this->captureRenderException(static fn(): string => $engine->render($textTemplate, $textData));
        $compiledText = $this->compiledRenderer($engine, $textTemplate);
        $compiledTextException = $this->captureRenderException(static function () use (
            $compiledText,
            $textData,
        ): string {
            $result = $compiledText($textData, new RenderContext(), new ComponentRenderer(new ComponentRegistry()));

            return is_string($result) ? $result : throw new UnexpectedValueException('Expected string output.');
        });

        foreach ([$directText, $compiledTextException] as $exception) {
            self::assertStringContainsString('Could not convert output to string', $exception->getMessage());
            $location = $exception->location();
            self::assertNotNull($location);
            self::assertSame(2, $location->line);
            self::assertSame(4, $location->byteColumn);
            $previous = $exception->getPrevious();
            self::assertInstanceOf(RuntimeException::class, $previous);
            self::assertSame($causeMessage, $previous->getMessage());
        }
        self::assertSame($directText->getMessage(), $compiledTextException->getMessage());
    }

    /** @return callable(array<string, mixed>, RenderContext, ComponentRenderer): mixed */
    private function compiledRenderer(TemplateEngine $engine, string $template): callable
    {
        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-context-');
        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));
        $compiled = require $path;
        unlink($path);
        self::assertIsCallable($compiled);

        return $compiled;
    }

    /** @param callable(): string $render */
    private function captureRenderException(callable $render): RenderException
    {
        try {
            $render();
            self::fail('Expected rendering to fail.');
        } catch (RenderException $exception) {
            return $exception;
        }
    }

    private function engine(): TemplateEngine
    {
        return new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
    }
}
