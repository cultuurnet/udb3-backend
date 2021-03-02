<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use PHPUnit\Framework\TestCase;

class DescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_not_be_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string should not be empty.');

        new Description('');
    }

    /**
     * @test
     */
    public function it_should_return_the_original_string()
    {
        $string = 'test foo bar';
        $description = new Description($string);
        $this->assertEquals($string, $description->toString());
    }
}
