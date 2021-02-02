<?php

namespace CultuurNet\UDB3;

use \CultuurNet\UDB3\Model\ValueObject\Text\Title as Udb3ModelTitle;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    public function emptyStringValues()
    {
        return array(
            array(''),
            array(' '),
            array('   '),
        );
    }

    /**
     * @test
     * @dataProvider emptyStringValues()
     * @param string $emptyStringValue
     */
    public function it_can_not_be_empty($emptyStringValue)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Title can not be empty.');
        new Title($emptyStringValue);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_title()
    {
        $udb3ModelTitle = new Udb3ModelTitle('foo bar');

        $expected = new Title('foo bar');
        $actual = Title::fromUdb3ModelTitle($udb3ModelTitle);

        $this->assertEquals($expected, $actual);
    }
}
