<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UpdateTypicalBirthDateTest extends TestCase
{
    /**
     * @test
     */
    public function it_stores_an_item_id_and_typical_birth_date(): void
    {
        $birthDate = new DateTimeImmutable('2020-03-15');
        $command = new UpdateTypicalBirthDate('event-id', $birthDate);

        $this->assertEquals('event-id', $command->getItemId());
        $this->assertEquals($birthDate, $command->getTypicalBirthDate());
    }
}
