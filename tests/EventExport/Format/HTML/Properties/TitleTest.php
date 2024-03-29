<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_at_least_one_character_long(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Title('');
    }
}
