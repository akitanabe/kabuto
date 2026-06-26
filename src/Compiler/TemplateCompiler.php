<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\ElementNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\PropNode;
use Kabuto\Ast\SlotOutletNode;
use Kabuto\Ast\TextNode;

final class TemplateCompiler
{
    /**
     * Compiles top-level AST nodes into an executable PHP renderer closure.
     *
     * @param list<Node> $nodes
     */
    public function compile(array $nodes): string
    {
        return (
            'return static function (array $data, \\Kabuto\\RenderContext $context, '
            . '\\Kabuto\\ComponentRenderer $renderer): string {'
            . "\n"
            . '    return '
            . $this->compileNodes($nodes)
            . ';'
            . "\n"
            . '};'
        );
    }

    /**
     * Compiles a list of nodes into a string expression.
     *
     * @param list<Node> $nodes
     */
    private function compileNodes(array $nodes): string
    {
        if ($nodes === []) {
            return "''";
        }

        return implode(' . ', array_map($this->compileNode(...), $nodes));
    }

    /**
     * Compiles a single AST node into a string expression.
     */
    private function compileNode(Node $node): string
    {
        if ($node instanceof TextNode) {
            return $this->string($node->content());
        }

        if ($node instanceof ElementNode) {
            return $this->compileElement($node);
        }

        if ($node instanceof ComponentNode) {
            return $this->compileComponent($node);
        }

        if ($node instanceof SlotOutletNode) {
            $slotName = $node->name() === null ? 'null' : $this->string($node->name());

            return '$renderer->slotOutlet(' . $slotName . ', $context)';
        }

        throw CompileException::unsupportedNode($node);
    }

    /**
     * Compiles a normal HTML element and its children.
     */
    private function compileElement(ElementNode $node): string
    {
        $openTag = '<' . $node->name();

        foreach ($node->attributes() as $attribute) {
            $openTag .=
                ' '
                . $attribute->name()
                . '="'
                . htmlspecialchars($attribute->value(), ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8')
                . '"';
        }

        $openTag .= '>';

        return (
            $this->string($openTag)
            . ' . '
            . $this->compileNodes($node->children())
            . ' . '
            . $this->string('</' . $node->name() . '>')
        );
    }

    /**
     * Compiles a component invocation through the runtime component renderer.
     */
    private function compileComponent(ComponentNode $node): string
    {
        return (
            '$renderer->component('
            . $this->string($node->name())
            . ', '
            . $this->compileProps($node->attributes(), $node->props())
            . ', '
            . $this->compileSlot($node->children())
            . ', '
            . $this->compileNamedSlots($node->slots())
            . ', $context)'
        );
    }

    /**
     * Compiles static attributes and dynamic props into a component props array.
     *
     * @param list<AttributeNode> $attributes
     * @param list<PropNode> $props
     */
    private function compileProps(array $attributes, array $props): string
    {
        $entries = [];

        foreach ($attributes as $attribute) {
            $entries[] = $this->string($attribute->name()) . ' => ' . $this->string($attribute->value());
        }

        foreach ($props as $prop) {
            $entries[] = $this->string($prop->name()) . ' => ' . $this->compileDataLookup($prop->expression());
        }

        return '[' . implode(', ', $entries) . ']';
    }

    /**
     * Compiles default slot children into a runtime Slot instance.
     *
     * @param list<Node> $children
     */
    private function compileSlot(array $children): string
    {
        if ($children === []) {
            return 'null';
        }

        return (
            'new \\Kabuto\\Slot(static function (\\Kabuto\\RenderContext $context) use ($data, $renderer): string {'
            . ' return '
            . $this->compileNodes($children)
            . '; })'
        );
    }

    /**
     * Compiles named slots into runtime Slot instances keyed by name.
     *
     * @param array<string, list<Node>> $slots
     */
    private function compileNamedSlots(array $slots): string
    {
        $entries = [];

        foreach ($slots as $name => $children) {
            $entries[] = $this->string($name) . ' => ' . $this->compileSlot($children);
        }

        return '[' . implode(', ', $entries) . ']';
    }

    /**
     * Compiles a simple `$name` dynamic expression into a render data lookup.
     */
    private function compileDataLookup(string $expression): string
    {
        return '($data[' . $this->string(substr($expression, offset: 1)) . '] ?? null)';
    }

    /**
     * Converts a PHP string value into a source-code literal.
     */
    private function string(string $value): string
    {
        return var_export($value, return: true);
    }
}
