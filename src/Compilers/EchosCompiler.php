<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Closure;
use Kabuto\CompilingContents;
use Kabuto\Compilers\Compiler;

class EchosCompiler extends Compiler
{
    public const RAW_TAGS = ['{!!', '!!}', '%s'];

    public const REGULAR_TAGS = ['{{', '}}', 'h(%s)'];

    public const ESCAPED_TAGS = ['{{{', '}}}', 's(%s)'];

    public array $uses = [
        'function Kabuto\Functions\Echos\h',
        'function Kabuto\Functions\Echos\s',
    ];

    public function compile(
        string $targetContents,
        ?array $tagsSet = null,
    ): CompilingContents {
        $compileEcho = $this->compileEcho(...);
        $searchEchoOpenTag = $this->searchEchoOpenTag(...);

        $tagsSet ??= [self::RAW_TAGS, self::ESCAPED_TAGS, self::REGULAR_TAGS];

        // compile echos
        $targetContents = array_reduce($tagsSet, $compileEcho, $targetContents);

        // search echo open tag
        [$addContents, $restContents, $openTags] = array_reduce(
            $tagsSet,
            $searchEchoOpenTag,
            [$targetContents, '', null],
        );

        $openTagsSet = isset($openTags) ? [$openTags] : null;
        if (isset($openTags) === false && $addContents !== '') {
            $lastChar = $addContents[-1];
            $last2Char = ($addContents[-2] ?? '') . $lastChar;

            if ($lastChar === '{') {
                $openTagsSet = [
                    self::RAW_TAGS,
                    self::ESCAPED_TAGS,
                    self::REGULAR_TAGS,
                ];

                $restContents = $lastChar;
                $addContents = substr($addContents, 0, -1);
            } elseif ($last2Char === '{!') {
                $openTagsSet = [self::RAW_TAGS];
                $restContents = $last2Char;
                $addContents = substr($addContents, 0, -2);
            }
        } elseif (
            isset($openTags) &&
            $openTags[0] === self::REGULAR_TAGS[0] &&
            $restContents === self::REGULAR_TAGS[0]
        ) {
            $openTagsSet = [self::ESCAPED_TAGS, self::REGULAR_TAGS];
        }

        $contents = [$addContents, $restContents];
        $todo = $this->getTodo($openTagsSet);

        return new CompilingContents($contents, $todo);
    }
    /**
     * @param string $targetContents
     * @param array{string, string, string} $tagSet
     *
     * @return string
     *
     */
    protected function compileEcho(string $targetContents, array $tags): string
    {
        [$openTag, $closeTag, $format] = $tags;

        $regexp = sprintf(
            '/%s\s*(.+?)\s*%s/s',
            preg_quote($openTag),
            preg_quote($closeTag),
        );

        $callback = function (array $matches) use ($format): string {
            [$_, $varName] = $matches;
            $var = sprintf($format, "$" . ltrim($varName, "$"));
            return "<?php echo {$var}; ?>";
        };

        return preg_replace_callback($regexp, $callback, $targetContents);
    }

    /**
     * @param array{string, string} $contents
     * @param array{string, string, string} $tag
     *
     * @return array{string, string, ?array}
     *
     */
    protected function searchEchoOpenTag(array $contents, array $tags): array
    {
        [$targetContents, $restContents] = $contents;

        if ($restContents !== '') {
            return $contents;
        }

        [$openTag] = $tags;

        $pos = strpos($targetContents, $openTag);

        if ($pos !== false) {
            $addContents = substr($targetContents, 0, $pos);
            $restContents = substr($targetContents, $pos);
            $openTags = $tags;
        } else {
            $addContents = $targetContents;
            $openTags = null;
        }

        return [$addContents, $restContents, $openTags];
    }

    protected function getTodo(?array $openTagsSet): ?Closure
    {
        if ($openTagsSet === null) {
            return null;
        }

        return fn(
            string $tagetContents,
        ) => $this->compile($tagetContents, $openTagsSet);
    }
}
