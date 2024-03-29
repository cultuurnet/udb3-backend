<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;

class UpdateBookingInfoTest extends TestCase
{
    /**
     * @var UpdateBookingInfo
     */
    protected $updateBookingInfo;

    public function setUp(): void
    {
        $this->updateBookingInfo = new UpdateBookingInfo(
            'id',
            new BookingInfo(
                'http://foo.bar',
                new MultilingualString(new Language('nl'), 'urlLabel'),
                '0123456789',
                'foo@bar.com',
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-01T00:00:00+01:00'),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-31T00:00:00+01:00')
            )
        );
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters(): void
    {
        $expectedUpdateBookingInfo = new UpdateBookingInfo(
            'id',
            new BookingInfo(
                'http://foo.bar',
                new MultilingualString(new Language('nl'), 'urlLabel'),
                '0123456789',
                'foo@bar.com',
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-01T00:00:00+01:00'),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-01-31T00:00:00+01:00')
            )
        );

        $this->assertEquals($expectedUpdateBookingInfo, $this->updateBookingInfo);
    }
}
