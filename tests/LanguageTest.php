<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_an_iso_639_1_code()
    {
        $language = new Language('en');

        $this->assertEquals('en', $language->getCode());
    }

    /**
     * Data provider with invalid codes.
     *
     * @return array
     */
    public function invalidCodes()
    {
        return [
            ['eng'],
            ['dut'],
            [false],
            [true],
            [null],
            ['09'],
            ['whatever'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidCodes
     * @param mixed $invalid_code
     */
    public function it_refuses_something_that_does_not_look_like_a_iso_639_1_code(
        $invalid_code
    ) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language code: ' . $invalid_code);

        new Language($invalid_code);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_language()
    {
        $udb3ModelLanguage = new Udb3ModelLanguage('nl');
        $expected = new Language('nl');
        $actual = Language::fromUdb3ModelLanguage($udb3ModelLanguage);
        $this->assertEquals($expected, $actual);
    }
}
