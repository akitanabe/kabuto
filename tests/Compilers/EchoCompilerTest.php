<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Kabuto\Compilers\EchoCompiler;

class EchoCompilerTest extends TestCase
{
    private EchoCompiler $compiler;

    public function setUp(): void
    {
        $this->compiler = new EchoCompiler();
    }

    public function testRegularTagsCompile(): void
    {
        $targetContents = 'Hello, {{ $name }}';
        $compilingContents = $this->compiler->compile($targetContents);

        $format = $this->compiler->REGULAR_TAGS->format;

        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["Hello, <?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }

    public function testEscapedTagsCompile(): void
    {
        $targetContents = 'Hello, {{{ $name }}}';
        $compilingContents = $this->compiler->compile($targetContents);

        $format = $this->compiler->ESCAPED_TAGS->format;

        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["Hello, <?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }

    public function testRawTagsCompile(): void
    {
        $targetContents = 'Hello, {!! $name !!}';
        $compilingContents = $this->compiler->compile($targetContents);

        $format = $this->compiler->RAW_TAGS->format;

        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["Hello, <?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }

    public function testCompileWithTodo(): void
    {
        $targetContents = 'Hello, {{ $name';
        $compilingContents = $this->compiler->compile($targetContents);

        $this->assertEquals('Hello, ', $compilingContents->addContents);

        $this->assertEquals('{{ $name', $compilingContents->restContents);

        $this->assertNotNull($compilingContents->todo);

        $compilingContents = $compilingContents->todo(
            $compilingContents->restContents . ' }}',
        );

        $format = $this->compiler->REGULAR_TAGS->format;
        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["<?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }

    public function testCompileWithTodoOneMissing(): void
    {
        $targetContents = 'Hello, {';
        $compilingContents = $this->compiler->compile($targetContents);

        $this->assertEquals('Hello, ', $compilingContents->addContents);

        $this->assertEquals('{', $compilingContents->restContents);

        $this->assertNotNull($compilingContents->todo);

        $compilingContents = $compilingContents->todo(
            $compilingContents->restContents . '{ $name}}',
        );

        $format = $this->compiler->REGULAR_TAGS->format;
        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["<?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }

    public function testCompileWithTodoTwoMissing(): void
    {
        $targetContents = 'Hello, {!';
        $compilingContents = $this->compiler->compile($targetContents);

        $this->assertEquals('Hello, ', $compilingContents->addContents);

        $this->assertEquals('{!', $compilingContents->restContents);

        $this->assertNotNull($compilingContents->todo);

        $compilingContents = $compilingContents->todo(
            $compilingContents->restContents . '! $name !!}',
        );

        $format = $this->compiler->RAW_TAGS->format;
        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["<?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }

    public function testCompileWithTodoTwoToThreeMissing(): void
    {
        $targetContents = 'Hello, {{';
        $compilingContents = $this->compiler->compile($targetContents);

        $this->assertEquals('Hello, ', $compilingContents->addContents);

        $this->assertEquals('{{', $compilingContents->restContents);

        $this->assertNotNull($compilingContents->todo);

        $compilingContents = $compilingContents->todo(
            $compilingContents->restContents . '{ $name }}}',
        );

        $format = $this->compiler->ESCAPED_TAGS->format;
        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["<?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }
}
