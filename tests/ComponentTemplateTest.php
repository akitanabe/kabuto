<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\Parser\ParseException;
use Kabuto\RenderContext;
use Kabuto\TemplateEngine;
use Kabuto\TemplateLoader;
use Kabuto\TemplateNotFoundException;
use Kabuto\Tests\Fixtures\ComponentTemplateCardComponent;
use Kabuto\Tests\Fixtures\ComponentTemplateComponent;
use Kabuto\Tests\Fixtures\ComponentTemplateLayoutComponent;
use Kabuto\Tests\Fixtures\NestedBrokenTemplateComponent;
use Kabuto\Tests\Fixtures\TemplateUserCardComponent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ComponentTemplateTest extends TestCase
{
    /**
     * Confirms that file rendering loads root-relative templates through the loader.
     */
    #[Test]
    public function engineRendersTemplateFile(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'user-card' => TemplateUserCardComponent::class,
        ])), loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        self::assertSame("<article>Alice</article>\n", $engine->renderFile('user-card.kabuto', [
            'user' => 'Alice',
        ]));
    }

    /**
     * Confirms that file parse errors report the root-relative template path.
     */
    #[Test]
    public function renderFileReportsTemplatePathLineAndColumnForParseErrors(): void
    {
        $engine = new TemplateEngine(
            new ComponentRenderer(new ComponentRegistry()),
            loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'),
        );

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Expected name at broken-render.kbt:2:2.');

        $engine->renderFile('broken-render.kbt');
    }

    /**
     * Confirms that an inner renderFile parse error keeps its own template path.
     */
    #[Test]
    public function nestedRenderFileKeepsInnerTemplatePathForParseErrors(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'nested-broken-template' => NestedBrokenTemplateComponent::class,
        ])), loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Expected name at nested-broken.kbt:2:2.');

        $engine->renderFile('outer-broken-wrapper.kbt');
    }

    /**
     * Confirms that components can delegate rendering to a template file.
     */
    #[Test]
    public function componentDelegatesRenderingToTemplateFile(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'template-card' => ComponentTemplateComponent::class,
            'component-template-card' => ComponentTemplateCardComponent::class,
        ])), loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        self::assertSame("<article>Alice in R&amp;D</article>\n", $engine->render(
            '<k-template-card :name="$name" />',
            [
                'name' => 'Alice',
            ],
            context: new RenderContext([
                'place' => 'R&D',
            ]),
        ));
    }

    /**
     * Confirms that class components can render received slots through template outlets.
     */
    #[Test]
    public function componentTemplateRendersReceivedSlotOutlets(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry([
            'component-template-layout' => ComponentTemplateLayoutComponent::class,
        ])), loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));

        self::assertSame(
            "<header>Header</header><main>Body</main>\n",
            $engine->render(
                '<k-component-template-layout><k-slot name="header">Header</k-slot>Body</k-component-template-layout>',
            ),
        );
    }

    /**
     * Confirms that component template rendering requires an injected template engine.
     */
    #[Test]
    public function componentViewRequiresTemplateEngine(): void
    {
        $component = new ComponentTemplateComponent(['name' => 'Alice']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TemplateEngine is not configured for component views.');

        $component->render(new RenderContext());
    }

    /**
     * Confirms that renderFile requires an explicitly configured loader.
     */
    #[Test]
    public function engineRequiresLoaderToRenderTemplateFile(): void
    {
        $engine = new TemplateEngine(new ComponentRenderer(new ComponentRegistry()));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TemplateLoader is not configured.');

        $engine->renderFile('user-card.kabuto');
    }

    /**
     * Confirms that the template loader rejects paths it cannot safely load.
     */
    #[Test]
    public function templateLoaderRejectsMissingOutsideAndNonFilePaths(): void
    {
        $loader = new TemplateLoader(__DIR__ . '/Fixtures/templates');

        foreach ([
            'missing.kabuto',
            '../outside-templates/outside.kabuto',
            '.',
        ] as $path) {
            try {
                $loader->load($path);
                self::fail('Expected template loading to fail.');
            } catch (TemplateNotFoundException $exception) {
                self::assertSame('Template not found: ' . $path, $exception->getMessage());
            }
        }
    }
}
