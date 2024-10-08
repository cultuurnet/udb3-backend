<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use PHPUnit\Framework\TestCase;

class RecordedOnTest extends TestCase
{
    private RecordedOn $recordedOn;

    protected function setUp(): void
    {
        $this->recordedOn = RecordedOn::fromBroadwayDateTime(
            DateTime::fromString('2018-01-16T12:13:33Z')
        );
    }

    /**
     * @test
     */
    public function it_can_be_created_from_a_domain_message(): void
    {
        $domainMessage = new DomainMessage(
            'uuid',
            1,
            new Metadata(),
            null,
            DateTime::fromString('2018-01-16T12:13:33Z')
        );

        $this->assertEquals(
            RecordedOn::fromDomainMessage($domainMessage),
            $this->recordedOn
        );
    }

    /**
     * @test
     */
    public function it_can_be_created_from_a_broadway_datetime(): void
    {
        $dateTime = DateTime::fromString('2018-01-16T12:13:33Z');

        $this->assertEquals(
            RecordedOn::fromBroadwayDateTime($dateTime),
            $this->recordedOn
        );
    }

    /**
     * @test
     */
    public function it_stores_a_recorded_on_date_time(): void
    {
        $this->assertTrue(
            DateTime::fromString('2018-01-16T12:13:33Z')->equals(
                $this->recordedOn->toBroadwayDateTime()
            )
        );
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_a_string(): void
    {
        $this->assertEquals(
            '2018-01-16T12:13:33+00:00',
            $this->recordedOn->toString()
        );
    }
}
