<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use PHPUnit\Framework\TestCase;

class CopyrightHolderTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_trim_the_given_value()
    {
        $original = ' Publiq  ';
        $expected = 'Publiq';

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

    /**
     * @test
     */
    public function it_should_not_be_smaller_than_2_chars()
    {
        $shortCopyrightHolder = '1';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("CopyrightHolder '$shortCopyrightHolder' should not be shorter than 2 chars.");

        new CopyrightHolder($shortCopyrightHolder);
    }

    /**
     * @test
     */
    public function it_should_not_be_bigger_than_250_chars()
    {
        $longCopyrightHolder = str_repeat('0123456789', 26);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("CopyrightHolder '$longCopyrightHolder' should not be longer than 250 chars.");

        new CopyrightHolder($longCopyrightHolder);
    }
}
