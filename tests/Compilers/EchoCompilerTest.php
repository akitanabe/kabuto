<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Kabuto\Compilers\EchoCompiler;
use Kabuto\Compilers\EchoTags;

class EchoCompilerTest extends TestCase
{
    private EchoCompiler $compiler;

    private $echoFormat = '<?php echo %s; ?>';

    public function setUp(): void
    {
        $this->compiler = new EchoCompiler();
    }

    protected function assertCompileContents(
        string $targetContents,
        string $format,
        string $varName = '$name',
    ): void {
        $compilingContents = $this->compiler->compile($targetContents);

        $var = sprintf($format, $varName);

        $this->assertEquals(
            [sprintf($this->echoFormat, $var), ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }

    public function testRegularTagsCompile(): void
    {
        $format = $this->compiler->REGULAR_TAGS->format;
        foreach (
            [
                '{{ $name }}',
                '{{$name}}',
                '{{
                    $name
                 }}',
            ]
            as $targetContents
        ) {
            $this->assertCompileContents($targetContents, $format);
        }
    }

    public function testEscapedTagsCompile(): void
    {
        $format = $this->compiler->ESCAPED_TAGS->format;
        foreach (
            [
                '{{{ $name }}}',
                '{{{$name}}}',
                '{{{
                    $name
                 }}}',
            ]
            as $targetContents
        ) {
            $this->assertCompileContents($targetContents, $format);
        }
    }

    public function testRawTagsCompile(): void
    {
        $format = $this->compiler->RAW_TAGS->format;
        foreach (
            [
                '{!! $name !!}',
                '{!!$name!!}',
                '{!!
                    $name
                 !!}',
            ]
            as $targetContents
        ) {
            $this->assertCompileContents($targetContents, $format);
        }
    }

    public function testCompileWithTodo(): void
    {
        foreach (
            [
                $this->compiler->RAW_TAGS,
                $this->compiler->ESCAPED_TAGS,
                $this->compiler->REGULAR_TAGS,
            ]
            as $tags
        ) {
            $targetContents = $tags->openTag . ' $name';
            $compilingContents = $this->compiler->compile($targetContents);

            $this->assertEquals('', $compilingContents->addContents);

            $this->assertEquals(
                $targetContents,
                $compilingContents->restContents,
            );

            $this->assertNotNull($compilingContents->todo);

            $compilingContents = $compilingContents->todo(
                $compilingContents->restContents . ' ' . $tags->closeTag,
            );

            $format = $tags->format;
            $var = sprintf($format, '$name');
            $this->assertEquals(
                ["<?php echo {$var}; ?>", ''],
                [
                    $compilingContents->addContents,
                    $compilingContents->restContents,
                ],
            );
        }
    }

    public function testCompileWithTodoLastChar(): void
    {
        $tagsSet = array_map(
            function (EchoTags $tags) {
                return [
                    substr($tags->openTag, 1),
                    $tags->closeTag,
                    $tags->format,
                ];
            },
            [
                $this->compiler->RAW_TAGS,
                $this->compiler->ESCAPED_TAGS,
                $this->compiler->REGULAR_TAGS,
            ],
        );

        $targetContents = '{';
        $compilingContents = $this->compiler->compile($targetContents);

        $this->assertEquals('', $compilingContents->addContents);
        $this->assertEquals('{', $compilingContents->restContents);

        foreach ($tagsSet as $tags) {
            [$restOpenTag, $closeTag, $format] = $tags;

            $this->assertNotNull($compilingContents->todo);

            $todoCompilingContents = $compilingContents->todo(
                $compilingContents->restContents .
                    "{$restOpenTag} \$name {$closeTag}",
            );

            $var = sprintf($format, '$name');
            $this->assertEquals(
                ["<?php echo {$var}; ?>", ''],
                [
                    $todoCompilingContents->addContents,
                    $todoCompilingContents->restContents,
                ],
            );
        }
    }

    public function testCompileWithTodoLast2Char(): void
    {
        $targetContents = '{!';
        $compilingContents = $this->compiler->compile($targetContents);

        $this->assertEquals('', $compilingContents->addContents);

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

    public function testCompileWithTodoRegularEchoTagOnly(): void
    {
        $targetContents = '{{';
        $compilingContents = $this->compiler->compile($targetContents);

        $this->assertEquals('', $compilingContents->addContents);

        $this->assertEquals('{{', $compilingContents->restContents);

        $this->assertNotNull($compilingContents->todo);

        $compilingContents = $compilingContents->todo(
            $compilingContents->restContents . '{ $name }}',
        );

        $format = $this->compiler->ESCAPED_TAGS->format;
        $var = sprintf($format, '$name');
        $this->assertEquals(
            ["<?php echo {$var}; ?>", ''],
            [$compilingContents->addContents, $compilingContents->restContents],
        );
    }
}
