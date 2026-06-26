<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use InvalidArgumentException;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\TemplateEngine;
use Kabuto\TemplateLoader;
use Kabuto\Tests\Fixtures\TemplateAlertComponent;
use Kabuto\Tests\Fixtures\TemplateUserCardComponent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TemplateOnlyComponentTest extends TestCase
{
    /**
     * Confirms that file rendering uses .kbt as the default template extension.
     */
    #[Test]
    public function engineRendersTemplateFileWithDefaultKbtExtension(): void
    {
        $engine = new TemplateEngine(
            new ComponentRenderer(new ComponentRegistry()),
            loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'),
        );

        self::assertSame("<article>Default</article>\n", $engine->renderFile('default-card'));
    }

    /**
     * Confirms that unregistered component names can fall back to same-name templates.
     */
    #[Test]
    public function engineRendersUnregisteredTemplateOnlyComponent(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'user-card' => TemplateUserCardComponent::class,
        ])), loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        self::assertSame("<article>Alice &amp; Bob</article>\n", $engine->render('<k-fallback-card :name="$name" />', [
            'name' => 'Alice & Bob',
        ]));
    }

    /**
     * Confirms that template-only components can render default and named slot outlets.
     */
    #[Test]
    public function engineRendersTemplateOnlyComponentSlotOutlets(): void
    {
        $engine = new TemplateEngine(
            new ComponentRenderer(new ComponentRegistry()),
            loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'),
        );

        self::assertSame(
            "<header>Header</header><main>Body</main>\n",
            $engine->render('<k-fallback-layout><k-slot name="header">Header</k-slot>Body</k-fallback-layout>'),
        );
    }

    /**
     * Confirms that explicit registry entries take precedence over template fallback files.
     */
    #[Test]
    public function registeredComponentTakesPrecedenceOverTemplateOnlyFallback(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'fallback-card' => TemplateAlertComponent::class,
        ])), loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        self::assertSame(
            '<aside data-type="info">Registered</aside>',
            $engine->render('<k-fallback-card type="info">Registered</k-fallback-card>'),
        );
    }

    /**
     * Confirms that missing template fallback keeps the unknown component error.
     */
    #[Test]
    public function missingTemplateOnlyComponentKeepsUnknownComponentError(): void
    {
        $engine = new TemplateEngine(
            new ComponentRenderer(new ComponentRegistry()),
            loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Component is not registered: missing-card');

        $engine->render('<k-missing-card />');
    }
}
