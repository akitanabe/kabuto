<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Ast\TextNode;
use Kabuto\Compiler\CompileException;
use Kabuto\Parser\SourceLocation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CompileExceptionTest extends TestCase
{
    /**
     * Confirms that compile errors can report template path and source location.
     */
    #[Test]
    public function compileExceptionReportsTemplatePathLineAndColumn(): void
    {
        $exception = CompileException::unsupportedNode(new TextNode('Hello'))
            ->withLocation(new SourceLocation(offset: 8, line: 3, byteColumn: 4))
            ->withTemplateName('widgets/card.kbt');

        self::assertSame(
            'Unsupported AST node: Kabuto\Ast\TextNode at widgets/card.kbt:3:4.',
            $exception->getMessage(),
        );
    }
}
