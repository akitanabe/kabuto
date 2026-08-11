<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\ExpressionRuntime;
use Kabuto\FilterRegistry;
use Kabuto\RenderContext;
use Kabuto\RenderException;
use Kabuto\TemplateEngine;
use Kabuto\TemplateLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class DynamicUrlOutputTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function allowedUrlProvider(): iterable
    {
        foreach ([
            '/path',
            '',
            'relative/path',
            '../path',
            '%2Fpath',
            '?q=1',
            '#id',
            'http://example.test',
            'https://example.test',
            'mailto:a@example.test',
            'tel:+123',
        ] as $url) {
            yield $url => [$url];
        }
    }

    #[Test]
    #[DataProvider('allowedUrlProvider')]
    public function allowedDynamicUrlsAreRendered(string $url): void
    {
        $this->assertDirectAndCompiled('<a :href="$url">link</a>', ['url' => $url], '<a href="' . $url . '">link</a>');
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedUrlProvider(): iterable
    {
        foreach ([
            '//host/path',
            'JaVaScRiPt:alert(1)',
            'DaTa:text/html,x',
            'VbScRiPt:x',
            'ftp://example.test',
            '/\\host/path',
            'https:\\host/path',
            '1http:foo',
            'not a path',
            "path\nnext",
            "path\0next",
            ':broken',
        ] as $url) {
            yield bin2hex($url) => [$url];
        }
    }

    #[Test]
    #[DataProvider('rejectedUrlProvider')]
    public function unsafeOrInvalidDynamicUrlsFailAtRenderLocation(string $url): void
    {
        $this->assertRenderFailureParity("<a\n :href=\"\$url\">link</a>", ['url' => $url], 'Invalid URL', 2, 9);
    }

    #[Test]
    public function omittedAndStaticUrlsDoNotRequireValidation(): void
    {
        $this->assertDirectAndCompiled('<a :href="$url">link</a>', ['url' => null], '<a>link</a>');
        $this->assertDirectAndCompiled('<a :href="$url">link</a>', ['url' => false], '<a>link</a>');
        $this->assertDirectAndCompiled(
            '<a href="javascript:alert(1)">link</a>',
            [],
            '<a href="javascript:alert(1)">link</a>',
        );
        $this->assertDirectAndCompiled(
            '<div :data="$url"></div>',
            ['url' => 'javascript:x'],
            '<div data="javascript:x"></div>',
        );

        foreach ([
            '<a :href="$url"></a>',
            '<img :src="$url">',
            '<form :action="$url"></form>',
            '<button :formaction="$url"></button>',
            '<video :poster="$url"></video>',
            '<blockquote :cite="$url"></blockquote>',
            '<div :itemid="$url"></div>',
            '<img :usemap="$url">',
            '<object :data="$url"></object>',
        ] as $template) {
            $exception = $this->captureRenderException(static fn(): string => new TemplateEngine(new ComponentRenderer(
                new ComponentRegistry(),
            ))->render($template, ['url' => 'javascript:x']));
            self::assertStringContainsString('Invalid URL', $exception->getMessage());
        }

        $filters = new FilterRegistry();
        $filters->register(
            'safeUrl',
            static fn(mixed $value): string => 'https://example.test/' . (is_scalar($value) ? $value : ''),
        );
        $renderer = new ComponentRenderer(new ComponentRegistry(), expressionRuntime: new ExpressionRuntime($filters));
        $engine = new TemplateEngine($renderer);
        $template = '<a :href="$url | safeUrl"></a>';
        $expected = '<a href="https://example.test/javascript:x"></a>';
        self::assertSame($expected, $engine->render($template, ['url' => 'javascript:x']));
        self::assertSame($expected, $this->compiledRenderer($engine, $template)(
            ['url' => 'javascript:x'],
            new RenderContext(),
            $renderer,
        ));
    }

    #[Test]
    public function renderFileAddsTemplatePathToUrlFailure(): void
    {
        $directory = sys_get_temp_dir() . '/kabuto-context-' . bin2hex(random_bytes(8));
        mkdir($directory);
        file_put_contents(filename: $directory . '/unsafe.kbt', data: '<a :href="$url">x</a>');
        $engine = new TemplateEngine(
            new ComponentRenderer(new ComponentRegistry()),
            loader: new TemplateLoader($directory),
        );

        try {
            $engine->renderFile('unsafe.kbt', ['url' => 'javascript:x']);
            self::fail('Expected unsafe URL to fail.');
        } catch (RenderException $exception) {
            self::assertStringContainsString('unsafe.kbt:1:11', $exception->getMessage());
        } finally {
            unlink($directory . '/unsafe.kbt');
            rmdir($directory);
        }
    }

    /** @param array<string, mixed> $data */
    private function assertDirectAndCompiled(string $template, array $data, string $expected): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
        self::assertSame($expected, $engine->render($template, $data));
        self::assertSame($expected, $this->compiledRenderer($engine, $template)(
            $data,
            new RenderContext(),
            new ComponentRenderer(new ComponentRegistry()),
        ));
    }

    /** @param array<string, mixed> $data */
    private function assertRenderFailureParity(
        string $template,
        array $data,
        string $message,
        int $line,
        int $column,
    ): void {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
        $direct = $this->captureRenderException(static fn(): string => $engine->render($template, $data));
        $compiled = $this->compiledRenderer($engine, $template);
        $compiledException = $this->captureRenderException(static function () use ($compiled, $data): string {
            $result = $compiled($data, new RenderContext(), new ComponentRenderer(new ComponentRegistry()));
            if (!is_string($result)) {
                throw new UnexpectedValueException('Compiled renderer must return a string.');
            }

            return $result;
        });

        self::assertStringContainsString($message, $direct->getMessage());
        $location = $direct->location();
        self::assertNotNull($location);
        self::assertSame($line, $location->line);
        self::assertSame($column, $location->byteColumn);
        self::assertSame($direct->getMessage(), $compiledException->getMessage());
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
}
