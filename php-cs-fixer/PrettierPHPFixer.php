<?php

declare(strict_types=1);

namespace PhpCsFixer;

use SplFileInfo;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for using prettier-php to fix.
 */
final class PrettierPHPFixer implements FixerInterface
{
    protected string $configPath = __DIR__ . '/../prettier.config.mjs';

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        // should be absolute first
        return 999;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        if (
            0 < $tokens->count() &&
            $this->isCandidate($tokens) &&
            $this->supports($file)
        ) {
            $this->applyFix($file, $tokens);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        $definition = new FixerDefinitionInterface();

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Prettier/php';
    }

    /**
     * {@inheritdoc}
     */
    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    private function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $output = [];

        $filepath = $file->getRealPath();
        $configPath = $this->configPath;

        exec("yarn exec -- prettier $filepath --config $configPath", $output);

        array_pop($output); // remove last
        array_shift($output); // remove first line

        $output[] = '';
        $content = join(PHP_EOL, $output);

        $tokens->setCode($content);
    }

    // prettier側でやるので無効化するルール
    public static $confingPrettier = [
        'binary_operator_spaces' => false,
        'braces_position' => false,
        'control_structure_continuation_position' => false,
        'concat_space' => false,
        'declare_equal_normalize' => false,
        'elseif' => false,
        'function_declaration' => false,
        'method_argument_space' => false,
        'no_blank_lines_after_class_opening' => false,
        'no_multiple_statements_per_line' => false,
        'no_space_around_double_colon' => false,
        'no_spaces_after_function_name' => false,
        'no_trailing_whitespace' => false,
        'no_whitespace_in_blank_line' => false,
        'return_type_declaration' => false,
        'single_class_element_per_statement' => false,
        'spaces_inside_parentheses' => false,
        'statement_indentation' => false,
        'switch_case_semicolon_to_colon' => false,
        'switch_case_space' => false,
        'ternary_operator_spaces' => false,
        'unary_operator_spaces' => false,
    ];
}
