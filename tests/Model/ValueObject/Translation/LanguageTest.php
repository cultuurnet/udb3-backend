<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_the_language_code_as_a_string(): void
    {
        $language = new Language('nl');
        $this->assertEquals('nl', $language->getCode());
        $this->assertEquals('nl', $language->toString());
    }

    /**
     * @test
     */
    public function it_should_not_accept_uppercase_language_codes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("String 'NL' does not match regex pattern /^[a-z]{2}$/.");

        new Language('NL');
    }
}
