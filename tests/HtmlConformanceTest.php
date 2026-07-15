<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Closure;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;
use Kabuto\TemplateEngine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class HtmlConformanceTest extends TestCase
{
    #[Test]
    #[TestWith(['area'])]
    #[TestWith(['base'])]
    #[TestWith(['br'])]
    #[TestWith(['col'])]
    #[TestWith(['embed'])]
    #[TestWith(['hr'])]
    #[TestWith(['img'])]
    #[TestWith(['input'])]
    #[TestWith(['link'])]
    #[TestWith(['meta'])]
    #[TestWith(['source'])]
    #[TestWith(['track'])]
    #[TestWith(['wbr'])]
    public function engineNormalizesEveryVoidElementAcrossCompilationPaths(string $name): void
    {
        $this->assertTemplateParity('<' . $name . '><span>after</span>', '<' . $name . '><span>after</span>');
        $this->assertTemplateParity('<' . $name . ' /><span>after</span>', '<' . $name . '><span>after</span>');
    }

    #[Test]
    public function engineSerializesHtmlBareAndEmptyAttributesAcrossCompilationPaths(): void
    {
        $this->assertTemplateParity(
            '<input required disabled="" hidden="" class="" data-state="" title="Save & close">',
            '<input required disabled hidden class="" data-state="" title="Save &amp; close">',
        );
    }

    #[Test]
    public function enginePreservesHtmlCommentsAndTopLevelHtmlDoctypeAcrossCompilationPaths(): void
    {
        $template = '<!dOcTyPe hTmL><!-- top --><main>before<!-- inside -->after</main>';

        $this->assertTemplateParity($template, $template);
    }

    #[Test]
    #[TestWith(['script'])]
    #[TestWith(['style'])]
    #[TestWith(['textarea'])]
    #[TestWith(['title'])]
    public function engineRendersRawTextContentsLiterallyAcrossCompilationPaths(string $name): void
    {
        $content = '@if ($ready)<k-card><!-- note --><span>literal</span>';
        $template = '<' . $name . '>' . $content . '</' . $name . '>';

        $this->assertTemplateParity($template, $template);
    }

    /**
     * Asserts identical output from direct rendering and both public compilation APIs.
     */
    private function assertTemplateParity(string $template, string $expected): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry());
        $engine = new TemplateEngine($renderer);

        self::assertSame($expected, $engine->render($template));
        self::assertSame($expected, $engine->compile($template)(
            [],
            new RenderContext(),
            $renderer->withTemplateEngine($engine),
        ));
        self::assertSame($expected, $this->renderCompiledPhp($engine, $renderer, $template));
    }

    /**
     * Executes PHP source produced by the public cache-file compilation API.
     */
    private function renderCompiledPhp(TemplateEngine $engine, ComponentRenderer $renderer, string $template): string
    {
        $path = tempnam(directory: sys_get_temp_dir(), prefix: 'kabuto-compiled-');

        self::assertIsString($path);
        file_put_contents($path, "<?php\n" . $engine->compilePhp($template));

        $compiled = require $path;
        unlink($path);

        self::assertInstanceOf(Closure::class, $compiled);
        $result = $compiled([], new RenderContext(), $renderer->withTemplateEngine($engine));

        self::assertIsString($result);

        return $result;
    }
}
