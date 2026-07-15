<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Closure;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;
use Kabuto\TemplateEngine;
use Kabuto\Tests\Fixtures\AttributePanelComponent;
use Kabuto\Tests\Fixtures\TemplateAttributeProbeComponent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ComponentAttributeRenderingTest extends TestCase
{
    /**
     * Confirms that component attributes stay separate from accepted dynamic props.
     */
    #[Test]
    public function engineSeparatesAttributesFromAcceptedDynamicProps(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'attribute-probe' => TemplateAttributeProbeComponent::class,
        ]));
        $engine = new TemplateEngine($renderer);
        $template = '<k-attribute-probe type="error" user="static" :user="$user" :unknown="$unknown">Body</k-attribute-probe>';
        $data = [
            'user' => 'Alice',
            'unknown' => 'ignored',
        ];

        $expected = '<section data-type="error" data-user-attribute="static">Alice|missing|missing|Body</section>';

        self::assertSame($expected, $engine->render($template, $data));
        self::assertSame($expected, $this->renderCompiledPhp($engine, $renderer, $template, $data));
    }

    #[Test]
    public function engineSerializesBareAndEmptyComponentAttributesAcrossCompilationPaths(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'panel' => AttributePanelComponent::class,
        ]));
        $engine = new TemplateEngine($renderer);
        $template = '<k-panel required disabled="" hidden="" class="" data-state="" title="Save & close" />';
        $expected = '<section class="panel" required disabled hidden data-state="" title="Save &amp; close">missing|no-prop|ignored</section>';

        self::assertSame($expected, $engine->render($template));
        self::assertSame($expected, $this->renderCompiledPhp($engine, $renderer, $template, []));
    }

    /**
     * Renders a PHP-compiled template through the same runtime renderer.
     *
     * @param array<string, mixed> $data
     */
    private function renderCompiledPhp(
        TemplateEngine $engine,
        ComponentRenderer $renderer,
        string $template,
        array $data,
    ): string {
        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-compiled-');

        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));

        $compiled = require $path;
        unlink($path);

        self::assertInstanceOf(Closure::class, $compiled);
        $result = $compiled($data, new RenderContext(), $renderer->withTemplateEngine($engine));

        self::assertIsString($result);

        return $result;
    }
}
