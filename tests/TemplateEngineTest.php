<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\BaseComponent;
use Kabuto\Compiler\CompiledTemplate;
use Kabuto\Compiler\TemplateCompiler;
use Kabuto\Component;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\Escaper;
use Kabuto\Parser\Parser;
use Kabuto\Provider;
use Kabuto\RenderContext;
use Kabuto\TemplateEngine;
use Kabuto\Tests\Fixtures\TemplateAlertComponent;
use Kabuto\Tests\Fixtures\TemplateLayoutComponent;
use Kabuto\Tests\Fixtures\TemplateUserCardComponent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TemplateEngineTest extends TestCase
{
    /**
     * Confirms that normal HTML and literal text are rendered from a parsed template.
     */
    #[Test]
    public function engineRendersHtmlTemplate(): void
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
    #[Test]
    public function engineRendersRegisteredComponentWithDefaultSlot(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'alert' => TemplateAlertComponent::class,
        ])));

        self::assertSame(
            '<aside data-type="error">保存に失敗しました</aside>',
            $engine->render('<k-alert type="error">保存に失敗しました</k-alert>'),
        );
    }

    /**
     * Confirms that simple dynamic props are read from render data.
     */
    #[Test]
    public function enginePassesDynamicPropsFromData(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'user-card' => TemplateUserCardComponent::class,
        ])));

        self::assertSame('<article>Alice</article>', $engine->render('<k-user-card :user="$user" />', [
            'user' => 'Alice',
        ]));
    }

    /**
     * Confirms that named slots are provided separately from the default slot.
     */
    #[Test]
    public function engineRendersNamedSlots(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'layout' => TemplateLayoutComponent::class,
        ])));

        self::assertSame(
            '<header>ヘッダー</header><main>本文</main>',
            $engine->render('<k-layout><k-slot name="header">ヘッダー</k-slot>本文</k-layout>'),
        );
    }

    /**
     * Confirms that provider components extend context for child components.
     */
    #[Test]
    public function engineRendersProviderWithScopedContext(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'provider' => Provider::class,
            'cart-summary' => $this->contextReader('cart'),
        ])));

        self::assertSame('<strong>1200</strong>', $engine->render('<k-provider name="cart" :value="$cart"><k-cart-summary /></k-provider>', [
            'cart' => 1200,
        ]));
    }

    /**
     * Confirms that store provider syntax maps to a namespaced component name.
     */
    #[Test]
    public function engineRendersStoreProvideComponentName(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'store:provide' => Provider::class,
            'cart-summary' => $this->contextReader('cart'),
        ])));

        self::assertSame('<strong>2400</strong>', $engine->render('<k-store:provide name="cart" :value="$cart"><k-cart-summary /></k-store:provide>', [
            'cart' => 2400,
        ]));
    }

    /**
     * Confirms that x-prefixed attributes stay HTML and custom prefixes render components.
     */
    #[Test]
    public function engineKeepsXAttributesAndRendersCustomPrefixComponents(): void
    {
        $defaultEngine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'alert' => TemplateAlertComponent::class,
        ])), parser: new Parser(componentPrefix: 'ui-'));

        self::assertSame(
            '<div x-data="{ open: true }">Menu</div>',
            $defaultEngine->render('<div x-data="{ open: true }">Menu</div>'),
        );
        self::assertSame(
            '<aside data-type="info">更新しました</aside>',
            $engine->render('<ui-alert type="info">更新しました</ui-alert>'),
        );
    }

    /**
     * Confirms that the compiler produces an executable PHP renderer closure.
     */
    #[Test]
    public function compilerProducesPhpRendererClosure(): void
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
    #[Test]
    public function engineCompilesTemplateToRenderer(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));
        $renderer = $engine->compile('Hello');

        self::assertInstanceOf(CompiledTemplate::class, $renderer);
        self::assertSame('Hello', $renderer([], new RenderContext(), new ComponentRenderer(new ComponentRegistry())));
    }

    /**
     * Creates a component factory that renders one context value.
     */
    private function contextReader(string $key): callable
    {
        return static fn(): Component => new class($key) extends BaseComponent {
            /**
             * Stores the context key read by this test component.
             */
            public function __construct(
                private readonly string $key,
            ) {}

            /**
             * Renders the configured context value.
             */
            public function render(RenderContext $context): string
            {
                return '<strong>' . Escaper::escape($context->get($this->key)) . '</strong>';
            }
        };
    }
}
