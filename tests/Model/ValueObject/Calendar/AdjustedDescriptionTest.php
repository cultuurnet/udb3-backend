<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AdjustedDescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_adjusted_description(): void
    {
        $description = new AdjustedDescription('Gesloten op eerste kerstdag');

        $this->assertEquals('Gesloten op eerste kerstdag', $description->toString());
    }

    /**
     * @test
     */
    public function it_creates_an_adjusted_description_with_max_length(): void
    {
        $maxLengthText = str_repeat('a', 1000);
        $description = new AdjustedDescription($maxLengthText);

        $this->assertEquals($maxLengthText, $description->toString());
    }

    /**
     * @test
     */
    public function it_throws_when_description_exceeds_max_length(): void
    {
        $tooLongText = str_repeat('a', 1001);

        $this->expectException(InvalidArgumentException::class);

        new AdjustedDescription($tooLongText);
    }

    /**
     * @test
     */
    public function it_throws_when_description_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AdjustedDescription('');
    }

    /**
     * @test
     */
    public function it_accepts_whitespace_characters(): void
    {
        $description = new AdjustedDescription('   ');

        // Whitespace is allowed, only truly empty string is not
        $this->assertEquals('   ', $description->toString());
    }
}
