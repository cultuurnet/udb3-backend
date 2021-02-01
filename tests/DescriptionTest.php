<?php

namespace CultuurNet\UDB3;

use \CultuurNet\UDB3\Model\ValueObject\Text\Description as Udb3ModelDescription;
use PHPUnit\Framework\TestCase;

class DescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_description()
    {
        $udb3ModelDescription = new Udb3ModelDescription('foo bar');

        $expected = new Description('foo bar');
        $actual = Description::fromUdb3ModelDescription($udb3ModelDescription);

        $this->assertEquals($expected, $actual);
    }
}
