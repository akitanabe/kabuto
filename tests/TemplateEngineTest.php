<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Compiler\CompiledTemplate;
use Kabuto\Compiler\TemplateCompiler;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;
use Kabuto\TemplateEngine;
use Kabuto\Tests\Fixtures\TemplateAlertComponent;
use Kabuto\Tests\Fixtures\TemplateLayoutComponent;
use Kabuto\Tests\Fixtures\TemplateUserCardComponent;
use PHPUnit\Framework\TestCase;

final class TemplateEngineTest extends TestCase
{
    /**
     * Confirms that normal HTML and literal text are rendered from a parsed template.
     */
    public function testEngineRendersHtmlTemplate(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));

        self::assertSame(
            '<section class="hero &amp; lead">Hello <strong>world</strong></section>',
            $engine->render('<section class="hero & lead">Hello <strong>world</strong></section>'),
        );
    }

    /**
     * Confirms that component attributes and the default slot are rendered through the registry.
     */
    public function testEngineRendersRegisteredComponentWithDefaultSlot(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'alert' => TemplateAlertComponent::class,
        ])));

        self::assertSame(
            '<aside data-type="error">保存に失敗しました</aside>',
            $engine->render('<x-alert type="error">保存に失敗しました</x-alert>'),
        );
    }

    /**
     * Confirms that simple dynamic props are read from render data.
     */
    public function testEnginePassesDynamicPropsFromData(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'user-card' => TemplateUserCardComponent::class,
        ])));

        self::assertSame('<article>Alice</article>', $engine->render('<x-user-card :user="$user" />', [
            'user' => 'Alice',
        ]));
    }

    /**
     * Confirms that named slots are provided separately from the default slot.
     */
    public function testEngineRendersNamedSlots(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'layout' => TemplateLayoutComponent::class,
        ])));

        self::assertSame(
            '<header>ヘッダー</header><main>本文</main>',
            $engine->render('<x-layout><x-slot name="header">ヘッダー</x-slot>本文</x-layout>'),
        );
    }

    /**
     * Confirms that the compiler produces an executable PHP renderer closure.
     */
    public function testCompilerProducesPhpRendererClosure(): void
    {
        $code = new TemplateCompiler()->compile([
            new \Kabuto\Ast\TextNode('Hello'),
        ]);

        self::assertStringContainsString('return static function', $code);
        self::assertStringContainsString("'Hello'", $code);
    }

    /**
     * Confirms that templates compile to reusable renderers without eval.
     */
    public function testEngineCompilesTemplateToRenderer(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
        $renderer = $engine->compile('Hello');

        self::assertInstanceOf(CompiledTemplate::class, $renderer);
        self::assertSame('Hello', $renderer([], new RenderContext(), new ComponentRenderer(new ComponentRegistry())));
    }
}
