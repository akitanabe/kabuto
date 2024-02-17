<?php

declare(strict_types=1);

require_once __DIR__ . '/php-cs-fixer/PrettierPHPFixer.php';

use PhpCsFixer\Finder;
use PhpCsFixer\Config;
use PhpCsFixer\PrettierPHPFixer;

$finder = (new Finder())->in(__DIR__ . '/src');

return (new Config())
    ->setFinder($finder)
    ->registerCustomFixers([new PrettierPHPFixer()])
    ->setRules([
        'Prettier/php' => true,
        '@PER-CS2.0' => true,
        ...PrettierPHPFixer::$confingPrettier,
    ]);
