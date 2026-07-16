<?php

declare(strict_types=1);

namespace Kabuto\Tests\Concerns;

use Kabuto\Parser\ParseException;
use Kabuto\Parser\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

trait HtmlParserRegressionTestCases
{
    #[Test]
    #[TestWith(['script'])]
    #[TestWith(['style'])]
    #[TestWith(['textarea'])]
    #[TestWith(['title'])]
    public function parserRejectsRawTextElementsWithoutClosingTags(string $name): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse('<' . $name . '>literal');
    }

    #[Test]
    #[TestWith(['<k-card :enabled />'])]
    #[TestWith(['<k-slot :name />'])]
    #[TestWith(['<k-slot name />'])]
    public function parserRejectsBareDynamicPropsAndValuelessSlotNames(string $source): void
    {
        $this->expectException(ParseException::class);

        new Parser()->parse($source);
    }
}
