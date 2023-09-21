<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Text\Description as Udb3ModelDescription;
use PHPUnit\Framework\TestCase;

class DescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_description(): void
    {
        $udb3ModelDescription = new Udb3ModelDescription('foo bar');

        $expected = new Description('foo bar');
        $actual = Description::fromUdb3ModelDescription($udb3ModelDescription);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_value_with_to_string(): void
    {
        $udb3ModelDescription = new Udb3ModelDescription('test');

        $this->assertEquals('test', $udb3ModelDescription->toString());
    }

    /**
     * @test
     */
    public function it_should_compare_two_strings(): void
    {
        $descriptionA = new Udb3ModelDescription('test');
        $descriptionB = new Udb3ModelDescription('test');

        $this->assertTrue($descriptionA->sameAs($descriptionB));
    }
}
