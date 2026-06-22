<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\Node;

final class Parser
{
    /**
     * Parses a template fragment into top-level AST nodes.
     *
     * @return list<Node>
     */
    public function parse(string $source): array
    {
        if (preg_match('/@(if|foreach|endif|endforeach)\b/', $source) === 1) {
            throw ParseException::at('Directives are not supported', 0);
        }

        return new TemplateParser(new SourceCursor($source))->parse();
    }
}
