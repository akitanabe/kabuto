<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Kabuto\Compilers\Compiler;
use Kabuto\CompilingTemplate;

class DirectiveBlock
{
    public function __construct(
        readonly string $directive,
        readonly string $statement,
    ) {}

    public function endDirective(): string
    {
        return "end{$this->directive}";
    }
}

class DirectiveCompiler extends Compiler
{
    public array $directives = ['if', 'foreach', 'while', 'for', 'switch'];

    public array $uses = [];

    /** @var DirectiveBlock[] $blocks */
    protected $blocks = [];

    public function compile(CompilingTemplate $template): CompilingTemplate
    {
        $regexp = '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x';

        $nextTemplate = '';
        $pendingTemplate = $template->pending;

        preg_match_all($regexp, $template->next, $matches);
        $offset = 0;

        for ($i = 0; isset($matches[0][$i]); $i++) {
            $lastBlock = array_pop($this->blocks);

            $replacement = $matches[0][$i];
            $directive = $matches[1][$i];
            $statement = trim($matches[4][$i] ?? '');

            if (
                in_array($directive, $this->directives, true) &&
                $statement !== ''
            ) {
                if ($lastBlock !== null) {
                    $this->blocks[] = $lastBlock;
                }
                $phpDirective = "<?php {$directive}({$statement}): ?>";

                $this->blocks[] = new DirectiveBlock($directive, $statement);
            } elseif ($lastBlock?->endDirective() === $directive) {
                $phpDirective = "<?php {$directive}; ?>";
            }

            $pos = mb_strpos($template->next, "@{$directive}", $offset);
            $length = $pos - $offset;

            $nextTemplate .= mb_substr($template->next, $offset, $length);
            $nextTemplate .= $phpDirective;

            $offset = $pos + strlen($replacement);
        }

        $nextTemplate .= mb_substr($template->next, $offset);

        return new CompilingTemplate($nextTemplate, $pendingTemplate);
    }
}
