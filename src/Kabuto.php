<?php

declare(strict_types=1);

namespace Kabuto;

require_once __DIR__ . '/../vendor/autoload.php';

use Amp\Future;
use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\WritableResourceStream;
use Kabuto\Compilers\Compiler;
use Kabuto\Compilers\EchosCompiler;
use Exception;

use function Amp\async;

/**
 * Class Kabuto
 *
 */

class Kabuto
{
    protected Future $future;

    protected $functions = [];

    public function __construct(private string $templateFile)
    {
        if (!file_exists($this->templateFile)) {
            throw new Exception('Template file not found');
        }

        $this->future = async(function () {
            $r_fp = fopen($this->templateFile, 'r');
            $w_fp = fopen(__DIR__ . '/../compiled.php', 'w');

            $r_stream = new ReadableResourceStream($r_fp);
            $w_stream = new WritableResourceStream($w_fp);

            $compiledContents = new CompiledContents('', '');

            $compilers = [new EchosCompiler()];

            $declareation = $this->getPhpDeclaration($compilers);

            $w_stream->write($declareation);

            while ($chunk = $r_stream->read()) {
                $targetContents = $compiledContents->restContents . $chunk;

                foreach ($compilers as $compiler) {
                    $compiledContents = $compiler->compile($targetContents);
                }

                $w_stream->write($compiledContents->addContents);
            }

            if ($compiledContents->restContents !== '') {
                $w_stream->write($compiledContents->restContents);
            }
        });
    }
    /**
     * @param Compiler[] $compilers
     *
     * @return string
     *
     */
    protected function getPhpDeclaration(array $compilers): string
    {
        $uses = array_reduce(
            $compilers,
            fn(array $uses, Compiler $compiler): array => [
                ...$uses,
                ...$compiler->uses,
            ],
            [],
        );

        $format = <<<PHP
        <?php
            declare(strict_types=1);

            namespace Kabuto\Compiled;
            %s

        ?>
        PHP;

        $declareUses = join(
            PHP_EOL . '    ',
            array_map(fn(string $use): string => "use {$use};", $uses),
        );

        return sprintf($format, $declareUses);
    }

    public function render()
    {
        $this->future->await();
    }
}
