<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_an_iso_639_1_code(): void
    {
        $language = new Language('en');

        $this->assertEquals('en', $language->getCode());
    }

    /**
     * Data provider with invalid codes.
     */
    public function invalidCodes(): array
    {
        return [
            ['eng'],
            ['dut'],
            ['09'],
            ['whatever'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidCodes
     */
    public function it_refuses_something_that_does_not_look_like_a_iso_639_1_code(
        string $invalid_code
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language code: ' . $invalid_code);

        new Language($invalid_code);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_language(): void
    {
        $udb3ModelLanguage = new Udb3ModelLanguage('nl');
        $expected = new Language('nl');
        $actual = Language::fromUdb3ModelLanguage($udb3ModelLanguage);
        $this->assertEquals($expected, $actual);
    }
}
