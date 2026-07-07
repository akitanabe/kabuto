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

    /**
     * Confirms that directive pre-scan errors report the matched source position.
     */
    #[Test]
    public function parserReportsLineAndByteColumnForDirectiveErrors(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Directives are not supported at line 2, column 3.');

        new Parser()->parse("ok\n  @if (\$ok)");
    }
}
