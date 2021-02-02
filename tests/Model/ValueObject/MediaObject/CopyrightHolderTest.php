<?php

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use PHPUnit\Framework\TestCase;

class CopyrightHolderTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_trim_the_given_value()
    {
        $original = " Publiq  ";
        $expected = "Publiq";

        $copyrightHolder = new CopyrightHolder($original);
        $actual = $copyrightHolder->toString();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_not_be_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given string should not be empty.');

        new CopyrightHolder('');
    }
}
