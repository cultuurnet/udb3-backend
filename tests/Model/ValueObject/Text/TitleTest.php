<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_the_string(): void
    {
        $string = 'test foo bar';
        $title = new Title($string);
        $this->assertEquals($string, $title->toString());
    }

    /**
     * @test
     */
    public function it_should_trim_the_string(): void
    {
        $string = '  test foo bar  ';
        $title = new Title($string);
        $this->assertEquals('test foo bar', $title->toString());
    }

    /**
     * @test
     */
    public function it_should_not_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string should not be empty.');

        new Title('');
    }

    /**
     * @test
     */
    public function it_should_not_be_empty_after_trimming(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string should not be empty.');

        new Title('     ');
    }
}
