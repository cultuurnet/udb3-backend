<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Text\Title as Udb3ModelTitle;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    public function emptyStringValues(): array
    {
        return [
            [''],
            [' '],
            ['   '],
        ];
    }

    /**
     * @test
     * @dataProvider emptyStringValues()
     */
    public function it_can_not_be_empty(string $emptyStringValue): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string should not be empty.');
        new Title($emptyStringValue);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_title(): void
    {
        $udb3ModelTitle = new Udb3ModelTitle('foo bar');

        $expected = new Title('foo bar');
        $actual = Title::fromUdb3ModelTitle($udb3ModelTitle);

        $this->assertEquals($expected, $actual);
    }
}
