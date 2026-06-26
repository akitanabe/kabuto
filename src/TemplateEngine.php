<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Compiler\CompiledTemplate;
use Kabuto\Compiler\TemplateCompiler;
use Kabuto\Compiler\TemplateRendererCompiler;
use Kabuto\Parser\Parser;
use RuntimeException;

final class TemplateEngine
{
    /**
     * Stores the parser, compiler, and runtime renderer used for template rendering.
     */
    public function __construct(
        private ComponentRenderer $renderer,
        private Parser $parser = new Parser(),
        private TemplateCompiler $compiler = new TemplateCompiler(),
        private TemplateRendererCompiler $rendererCompiler = new TemplateRendererCompiler(),
        private ?TemplateLoader $loader = null,
    ) {}

    /**
     * Renders a template string with render data and context.
     *
     * @param array<string, mixed> $data
     * @param array<string, Slot> $slots
     */
    public function render(
        string $template,
        array $data = [],
        ?RenderContext $context = null,
        ?Slot $slot = null,
        array $slots = [],
    ): string {
        $renderer = $this->compile($template);
        $runtimeRenderer = $this->renderer->withTemplateEngine($this)->withSlots($slot, $slots);

        return $renderer($data, $context ?? new RenderContext(), $runtimeRenderer);
    }

    /**
     * Loads a root-relative template file and renders it with render data and context.
     *
     * @param array<string, mixed> $data
     * @param array<string, Slot> $slots
     */
    public function renderFile(
        string $path,
        array $data = [],
        ?RenderContext $context = null,
        ?Slot $slot = null,
        array $slots = [],
    ): string {
        if ($this->loader === null) {
            throw new RuntimeException('TemplateLoader is not configured.');
        }

        return $this->render($this->loader->load($path), $data, $context, $slot, $slots);
    }

    /**
     * Compiles a template string into an executable renderer.
     */
    public function compile(string $template): CompiledTemplate
    {
        return $template |> $this->parser->parse(...) |> $this->rendererCompiler->compile(...);
    }

    /**
     * Compiles a template string into PHP source suitable for cache files.
     */
    public function compilePhp(string $template): string
    {
        return $template |> $this->parser->parse(...) |> $this->compiler->compile(...);
    }
}
