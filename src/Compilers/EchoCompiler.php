<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Closure;
use Kabuto\CompilingContents;
use Kabuto\Compilers\Compiler;
use Kabuto\Compilers\EchoTags;

final class EchoCompiler extends Compiler
{
    public readonly EchoTags $RAW_TAGS;

    public readonly EchoTags $REGULAR_TAGS;

    public readonly EchoTags $ESCAPED_TAGS;

    public array $uses = [
        'function Kabuto\Functions\Echos\h',
        'function Kabuto\Functions\Echos\s',
    ];

    public function __construct()
    {
        $this->RAW_TAGS = new EchoTags('{!!', '!!}', '%s');
        $this->REGULAR_TAGS = new EchoTags('{{', '}}', 'h(%s)');
        $this->ESCAPED_TAGS = new EchoTags('{{{', '}}}', 's(h(%s))');
    }

    public function compile(
        string $targetContents,
        ?array $tagsSet = null,
    ): CompilingContents {
        $compileEcho = $this->compileEcho(...);
        $searchEchoOpenTag = $this->searchEchoOpenTag(...);

        $tagsSet ??= [
            $this->RAW_TAGS,
            $this->ESCAPED_TAGS,
            $this->REGULAR_TAGS,
        ];

        // compile echos
        $targetContents = array_reduce($tagsSet, $compileEcho, $targetContents);

        // search echo open tag
        /** @var ?EchoTags $openTags */
        [$addContents, $restContents, $openTags] = array_reduce(
            $tagsSet,
            $searchEchoOpenTag,
            [$targetContents, '', null],
        );

        $openTagsSet = isset($openTags) ? [$openTags] : null;

        if (
            // 開始タグが変わる可能性があるので特定の文字が最終列にあれば各値上書き
            isset($openTagsSet) === false &&
            $addContents !== '' &&
            $restContents === ''
        ) {
            $overides = $this->overrideWithLastChar($addContents);

            if (isset($overides)) {
                [$addContents, $restContents, $openTagsSet] = $overides;
            }
        } elseif (
            // 現在の開始タグがREGULAR_TAGSであればESCAPED_TAGSが次の開始タグになる可能性があるのでtodoの置換リストに追加
            isset($openTags) &&
            $openTags->openTag === $this->REGULAR_TAGS->openTag &&
            $restContents === $this->REGULAR_TAGS->openTag
        ) {
            $openTagsSet = [$this->ESCAPED_TAGS, $this->REGULAR_TAGS];
        }

        $contents = [$addContents, $restContents];
        $todo = $this->getTodo($openTagsSet);

        return new CompilingContents($contents, $todo);
    }
    /**
     * @param string $targetContents
     * @param EchoTags $tags
     *
     * @return string
     *
     */
    protected function compileEcho(
        string $targetContents,
        EchoTags $tags,
    ): string {
        $regexp = sprintf(
            '/%s\s*(.+?)\s*%s/s',
            $tags->quoteOpenTag,
            $tags->quoteCloseTag,
        );

        $callback = function (array $matches) use ($tags): string {
            [$_, $varName] = $matches;
            $var = sprintf($tags->format, "$" . ltrim($varName, "$"));
            return "<?php echo {$var}; ?>";
        };

        return preg_replace_callback($regexp, $callback, $targetContents);
    }

    /**
     * @param array{string, string} $contents
     * @param EchoTags $tags
     *
     * @return array{string, string, ?EchoTags}
     *
     */
    protected function searchEchoOpenTag(array $contents, EchoTags $tags): array
    {
        [$targetContents, $restContents] = $contents;

        if ($restContents !== '') {
            return $contents;
        }

        $pos = strpos($targetContents, $tags->openTag);

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

    /**
     * @param ?EchoTags[] $openTagsSet
     *
     * @return ?Closure
     */
    protected function getTodo(?array $openTagsSet): ?Closure
    {
        if ($openTagsSet === null) {
            return null;
        }

        return fn(
            string $tagetContents,
        ) => $this->compile($tagetContents, $openTagsSet);
    }

    /**
     * @param string $addContents
     *
     * @return ?array
     *
     */
    protected function overrideWithLastChar(string $addContents): ?array
    {
        $lastChar = $addContents[-1];
        $last2Char = ($addContents[-2] ?? '') . $lastChar;

        if ($lastChar === '{') {
            return [
                // addContentsの最終の文字を削除
                substr($addContents, 0, -1),
                // restContentsに最終の文字を追加
                $lastChar,
                // 全ての置換リストを採用
                [$this->RAW_TAGS, $this->ESCAPED_TAGS, $this->REGULAR_TAGS],
            ];
        } elseif ($last2Char === '{!') {
            return [
                // addContentsの最終の2文字を削除
                substr($addContents, 0, -2),
                // restContentsに最終の2文字を追加
                $last2Char,
                // 置換リストにRAW_TAGSを採用
                [$this->RAW_TAGS],
            ];
        }

        return null;
    }
}
