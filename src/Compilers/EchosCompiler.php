<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Kabuto\CompilingContents;
use Kabuto\Compilers\Compiler;

class EchosCompiler extends Compiler
{
    protected $rawTags = ['{!!', '!!}', 'e(%s)'];

    protected $contentTags = ['{{', '}}', 'e(h(%s))'];

    protected $escapedTags = ['{{{', '}}}', 'e(s(%s))'];

    public array $uses = [
        'function Kabuto\Functions\Echos\e',
        'function Kabuto\Functions\Echos\h',
        'function Kabuto\Functions\Echos\s',
    ];

    public function compile(string $targetContents): CompilingContents
    {
        $tags = array_map(
            function (array $tags): array {
                [$openTag, $closeTag, $echo] = $tags;

                return [preg_quote($openTag), preg_quote($closeTag), $echo];
            },
            [$this->rawTags, $this->contentTags, $this->escapedTags],
        );

        $compileEcho = $this->compileEcho(...);
        $searchEchoOpenTag = $this->searchEchoOpenTag(...);

        // compile echos
        $targetContents = array_reduce($tags, $compileEcho, $targetContents);

        [$addContents, $restContents] = array_reduce(
            $tags,
            $searchEchoOpenTag,
            ['', $targetContents],
        );

        if ($addContents === '') {
            $addContents = $targetContents;
            $restContents = '';
        }

        return new CompilingContents($addContents, $restContents);
    }
    /**
     * @param string $targetContents
     * @param array{string, string, string} $tags
     *
     * @return string
     *
     */
    protected function compileEcho(string $targetContents, array $tags): string
    {
        [$openTag, $closeTag, $echo] = $tags;

        $regexp = sprintf('/%s(.+?)%s/s', $openTag, $closeTag);

        $callback = function (array $matches) use ($echo): string {
            [$_, $var] = $matches;

            return sprintf("<?php {$echo}; ?>", "$" . ltrim(trim($var), "$"));
        };

        return preg_replace_callback($regexp, $callback, $targetContents);
    }

    /**
     * @param array{string, string} $contents
     *
     */
    protected function searchEchoOpenTag(array $contents, array $tags): array
    {
        [$addContents, $targetContents] = $contents;

        if ($addContents !== '') {
            return [...$contents];
        }

        [$openTag] = $tags;

        $pos = strpos($targetContents, $openTag);

        if ($pos !== false) {
            $addContents = substr($targetContents, 0, $pos);
            $restContents = substr($targetContents, $pos);
        } else {
            $addContents = '';
            $restContents = $targetContents;
        }

        return [$addContents, $restContents];
    }
}
