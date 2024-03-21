<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Closure;
use Kabuto\CompilingTemplate;
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
        CompilingTemplate $template,
        ?array $tagsSet = null,
    ): CompilingTemplate {
        $compileEcho = $this->compileEcho(...);
        $searchEchoOpenTag = $this->searchEchoOpenTag(...);

        $tagsSet ??= [
            $this->RAW_TAGS,
            $this->ESCAPED_TAGS,
            $this->REGULAR_TAGS,
        ];

        // compile echos
        $targetTemplate = array_reduce($tagsSet, $compileEcho, $template->next);

        // search echo open tag
        /** @var ?EchoTags $openTags */
        [$nextTemplate, $pendingTemplate, $openTags] = array_reduce(
            $tagsSet,
            $searchEchoOpenTag,
            [$targetTemplate, '', null],
        );

        $openTagsSet = isset($openTags) ? [$openTags] : null;

        if (
            // 開始タグが変わる可能性があるので特定の文字が最終列にあれば各値上書き
            isset($openTagsSet) === false &&
            $nextTemplate !== '' &&
            $pendingTemplate === ''
        ) {
            $overrides = $this->overrideWithLastChar($nextTemplate);

            if (isset($overides)) {
                [$nextTemplate, $pendingTemplate, $openTagsSet] = $overrides;
            }
        }

        return new CompilingTemplate($nextTemplate, $pendingTemplate);
    }
    /**
     * @param string $targetTemplate
     * @param EchoTags $tags
     *
     * @return string
     *
     */
    protected function compileEcho(
        string $targetTemplate,
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

        return preg_replace_callback($regexp, $callback, $targetTemplate);
    }

    /**
     * @param array{string, string} $contents
     * @param EchoTags $tags
     *
     * @return array{string, string, ?EchoTags}
     *
     */
    protected function searchEchoOpenTag(
        array $templates,
        EchoTags $tags,
    ): array {
        [$targetTemplate, $pendingTemplate] = $templates;

        if ($pendingTemplate !== '') {
            return [...$templates];
        }

        $pos = strpos($targetTemplate, $tags->openTag);

        if ($pos !== false) {
            $nextTemplate = substr($targetTemplate, 0, $pos);
            $pendingTemplate = substr($targetTemplate, $pos);
            $openTags = $tags;
        } else {
            $nextTemplate = $targetTemplate;
            $openTags = null;
        }

        return [$nextTemplate, $pendingTemplate, $openTags];
    }

    /**
     * @param string $nextTemplate
     *
     * @return ?array
     *
     */
    protected function overrideWithLastChar(string $nextTemplate): ?array
    {
        $lastChar = $nextTemplate[-1];
        $last2Char = ($nextTemplate[-2] ?? '') . $lastChar;

        if ($lastChar === '{') {
            return [
                // addContentsの最終の文字を削除
                substr($nextTemplate, 0, -1),
                // restContentsに最終の文字を追加
                $lastChar,
                // 全ての置換リストを採用
                [$this->RAW_TAGS, $this->ESCAPED_TAGS, $this->REGULAR_TAGS],
            ];
        } elseif ($last2Char === '{!') {
            return [
                // addContentsの最終の2文字を削除
                substr($nextTemplate, 0, -2),
                // restContentsに最終の2文字を追加
                $last2Char,
                // 置換リストにRAW_TAGSを採用
                [$this->RAW_TAGS],
            ];
        }

        return null;
    }
}
