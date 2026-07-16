<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\Node;

final class Parser
{
    private ComponentPrefix $componentPrefix;

    /**
     * Stores parser configuration shared by each parsed template.
     */
    public function __construct(string $componentPrefix = 'k-')
    {
        $this->componentPrefix = new ComponentPrefix($componentPrefix);
    }

    /**
     * Parses a template fragment into top-level AST nodes.
     *
     * @return list<Node>
     */
    public function parse(string $source): array
    {
        try {
            return new TemplateParser(new SourceCursor($source), $this->componentPrefix)->parse();
        } catch (ParseException $exception) {
            throw $exception->withSource($source);
        }
    }
}
