<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Parser\ParseException;
use Kabuto\Parser\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParserDiagnosticsTest extends TestCase
{
    /**
     * Confirms that parse errors report one-based line and byte column.
     */
    #[Test]
    public function parserReportsLineAndByteColumnForParseErrors(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Expected name at line 2, column 5.');

        new Parser()->parse("prefix\nあ<");
    }
}
