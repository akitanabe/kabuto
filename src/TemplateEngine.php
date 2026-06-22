<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Compiler\CompiledTemplate;
use Kabuto\Compiler\TemplateCompiler;
use Kabuto\Compiler\TemplateRendererCompiler;
use Kabuto\Parser\Parser;

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
    ) {}

    /**
     * Renders a template string with render data and context.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?RenderContext $context = null): string
    {
        $renderer = $this->compile($template);

        return $renderer($data, $context ?? new RenderContext(), $this->renderer);
    }

    /**
     * Compiles a template string into an executable renderer.
     */
    public function compile(string $template): CompiledTemplate
    {
        return $this->rendererCompiler->compile($this->parser->parse($template));
    }

    /**
     * Compiles a template string into PHP source suitable for cache files.
     */
    public function compilePhp(string $template): string
    {
        return $this->compiler->compile($this->parser->parse($template));
    }
}
