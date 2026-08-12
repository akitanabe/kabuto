<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\ElementNode;
use Kabuto\Ast\ForeachNode;
use Kabuto\Ast\IfNode;
use Kabuto\Ast\InterpolationNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\SlotOutletNode;
use Kabuto\Ast\TextNode;
use Kabuto\Expression;

final class TemplateCompiler
{
    /**
     * Compiles top-level AST nodes into an executable PHP renderer closure.
     *
     * @param list<Node> $nodes
     */
    public function compile(array $nodes): string
    {
        $names = new PhpNameAllocator();
        $body = $this->emitNodes($nodes, new CompilerScope('$scope', '$context'), '$html', 1, $names);

        return (
            'return static function (array $data, \\Kabuto\\RenderContext $context, '
            . '\\Kabuto\\ComponentRenderer $renderer): string {'
            . "\n"
            . '    $scope = \\Kabuto\\RenderScope::root($data);'
            . "\n"
            . "    \$html = '';"
            . "\n"
            . $body
            . '    return $html;'
            . "\n"
            . '};'
        );
    }

    /**
     * @param list<Node> $nodes
     */
    private function emitNodes(
        array $nodes,
        CompilerScope $compilerScope,
        string $accumulator,
        int $indent,
        PhpNameAllocator $names,
    ): string {
        $source = '';

        foreach ($nodes as $node) {
            $source .= $this->emitNode($node, $compilerScope, $accumulator, $indent, $names);
        }

        return $source;
    }

    private function emitNode(
        Node $node,
        CompilerScope $compilerScope,
        string $accumulator,
        int $indent,
        PhpNameAllocator $names,
    ): string {
        if ($node instanceof TextNode) {
            return PhpSource::line($indent, $accumulator . ' .= ' . PhpSource::string($node->content()) . ';');
        }

        if ($node instanceof InterpolationNode) {
            return PhpSource::line(
                $indent,
                $accumulator
                . ' .= $renderer->renderText('
                . $this->compileExpressionData($node->expression())
                . ', '
                . $compilerScope->scope
                . ');',
            );
        }

        if ($node instanceof ElementNode) {
            return $this->emitElement($node, $compilerScope, $accumulator, $indent, $names);
        }

        if ($node instanceof ComponentNode) {
            return PhpSource::line(
                $indent,
                $accumulator . ' .= ' . $this->compileComponent($node, $compilerScope, $names) . ';',
            );
        }

        if ($node instanceof SlotOutletNode) {
            $slotName = $node->name() === null ? 'null' : PhpSource::string($node->name());

            return PhpSource::line(
                $indent,
                $accumulator . ' .= $renderer->slotOutlet(' . $slotName . ', ' . $compilerScope->context . ');',
            );
        }

        if ($node instanceof IfNode) {
            return $this->emitIf($node, $compilerScope, $accumulator, $indent, $names);
        }

        if ($node instanceof ForeachNode) {
            return $this->emitForeach($node, $compilerScope, $accumulator, $indent, $names);
        }

        throw CompileException::unsupportedNode($node);
    }

    private function emitElement(
        ElementNode $node,
        CompilerScope $compilerScope,
        string $accumulator,
        int $indent,
        PhpNameAllocator $names,
    ): string {
        $compiler = new ElementNodeCompiler();
        $source = PhpSource::line(
            $indent,
            $accumulator
            . ' .= '
            . $compiler->compileOpenTag($node, $this->compileExpressionData(...), $compilerScope->scope)
            . ';',
        );
        $source .= $this->emitNodes($node->children(), $compilerScope, $accumulator, $indent, $names);
        $closingTag = $compiler->closingTag($node);

        if ($closingTag !== null) {
            $source .= PhpSource::line($indent, $accumulator . ' .= ' . PhpSource::string($closingTag) . ';');
        }

        return $source;
    }

    private function emitIf(
        IfNode $node,
        CompilerScope $compilerScope,
        string $accumulator,
        int $indent,
        PhpNameAllocator $names,
    ): string {
        $source = '';

        foreach ($node->branches() as $index => $branch) {
            $keyword = $index === 0 ? 'if' : 'elseif';
            $source .= PhpSource::line(
                $indent,
                $keyword
                . ' (\\Kabuto\\ControlFlow::condition($renderer->evaluate('
                . $this->compileExpressionData($branch->condition())
                . ', '
                . $compilerScope->scope
                . '))) {',
            );
            $source .= $this->emitNodes($branch->children(), $compilerScope, $accumulator, $indent + 1, $names);
            $source .= PhpSource::line($indent, '}');
        }

        $elseChildren = $node->elseChildren();
        if ($elseChildren !== null) {
            $source = rtrim(string: $source, characters: "\n") . ' else {' . "\n";
            $source .= $this->emitNodes($elseChildren, $compilerScope, $accumulator, $indent + 1, $names);
            $source .= PhpSource::line($indent, '}');
        }

        return $source;
    }

    private function emitForeach(
        ForeachNode $node,
        CompilerScope $compilerScope,
        string $accumulator,
        int $indent,
        PhpNameAllocator $names,
    ): string {
        $collection = $names->next('collection');
        $value = $names->next('value');
        $iterationScope = $names->next('scope');
        $expression = $node->collection();
        $source = PhpSource::line(
            $indent,
            $collection
            . ' = \\Kabuto\\ControlFlow::iterable($renderer->evaluate('
            . $this->compileExpressionData($expression)
            . ', '
            . $compilerScope->scope
            . '), '
            . PhpSource::location($expression->location())
            . ', '
            . $expression->offset()
            . ');',
        );
        $source .= PhpSource::line($indent, 'foreach (' . $collection . ' as ' . $value . ') {');
        $source .= PhpSource::line(
            $indent + 1,
            $iterationScope
            . ' = '
            . $compilerScope->scope
            . '->with('
            . PhpSource::string($node->item())
            . ', '
            . $value
            . ');',
        );
        $source .= $this->emitNodes(
            $node->children(),
            new CompilerScope($iterationScope, $compilerScope->context),
            $accumulator,
            $indent + 1,
            $names,
        );
        $source .= PhpSource::line($indent, '}');

        return $source;
    }

    private function compileComponent(
        ComponentNode $node,
        CompilerScope $compilerScope,
        PhpNameAllocator $names,
    ): string {
        $slots = [];
        foreach ($node->slots() as $name => $children) {
            $slots[] = PhpSource::string($name) . ' => ' . $this->compileSlot($children, $compilerScope->scope, $names);
        }

        return new ComponentNodeCompiler()->compile(
            $node,
            $this->compileExpressionData(...),
            $this->compileSlot($node->children(), $compilerScope->scope, $names),
            '[' . implode(', ', $slots) . ']',
            $compilerScope,
        );
    }

    /**
     * @param list<Node> $children
     */
    private function compileSlot(array $children, string $scope, PhpNameAllocator $names): string
    {
        if ($children === []) {
            return 'null';
        }

        $slotContext = $names->next('slotContext');
        $slotHtml = $names->next('slotHtml');

        return (
            'new \\Kabuto\\Slot(static function (\\Kabuto\\RenderContext '
            . $slotContext
            . ') use ('
            . $scope
            . ', $renderer): string {'
            . "\n"
            . '    '
            . $slotHtml
            . " = '';\n"
            . $this->emitNodes($children, new CompilerScope($scope, $slotContext), $slotHtml, 1, $names)
            . '    return '
            . $slotHtml
            . ';'
            . "\n"
            . '})'
        );
    }

    private function compileExpressionData(Expression $expression): string
    {
        return (
            'new \\Kabuto\\Expression('
            . PhpSource::string($expression->variable())
            . ', '
            . var_export($expression->filters(), return: true)
            . ', '
            . PhpSource::string($expression->source())
            . ', '
            . PhpSource::location($expression->location())
            . ', ['
            . implode(', ', array_map(PhpSource::location(...), $expression->filterLocations()))
            . ']'
            . ')'
        );
    }
}
