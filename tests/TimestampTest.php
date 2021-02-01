<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
{
    const START_DATE_KEY = 'startDate';
    const END_DATE_KEY = 'endDate';

    const START_DATE = '2016-01-03T01:01:01+01:00';
    const END_DATE = '2016-01-07T01:01:01+01:00';

    /**
     * @var Timestamp
     */
    private $timestamp;

    public function setUp(): void
    {
        $this->timestamp = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE)
        );
    }

    /**
     * @test
     */
    public function it_stores_a_start_and_end_date(): void
    {
        $this->assertEquals(
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            $this->timestamp->getStartDate()
        );

        $this->assertEquals(
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            $this->timestamp->getEndDate()
        );
    }

    /**
     * @test
     */
    public function its_end_date_can_not_be_earlier_than_start_date(): void
    {
        $pastDate = '2016-01-03T00:01:01+01:00';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('End date can not be earlier than start date.');

        new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, $pastDate)
        );
    }

    /**
     * @test
     */
    public function it_will_add_the_default_event_status(): void
    {
        $timestamp = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE)
        );

        $this->assertEquals(
            new Status(StatusType::available(), []),
            $timestamp->getStatus()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize_and_deserialize(): void
    {
        $timestamp = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            new Status(
                StatusType::unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Vanavond niet, schat'),
                ]
            )
        );

        $serialized = [
            'startDate' => self::START_DATE,
            'endDate' => self::END_DATE,
            'status' => [
                'type' => StatusType::unavailable()->toNative(),
                'reason' => [
                    'nl' => 'Vanavond niet, schat',
                ],
            ],
        ];

        $this->assertEquals($serialized, $timestamp->serialize());
        $this->assertEquals($timestamp, Timestamp::deserialize($serialized));
    }

    /**
     * @test
     */
    public function itCanChangeStatus(): void
    {
        $timestamp = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            new Status(StatusType::available(), [])
        );

        $newStatus = new Status(
            StatusType::unavailable(),
            [
                new StatusReason(new Language('nl'), 'Het mag niet van de afgevaardigde van de eerste minister'),
            ]
        );

        $expected = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            $newStatus
        );

        $this->assertEquals(
            $expected,
            $timestamp->withStatus($newStatus)
        );
    }
}
