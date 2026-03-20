<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SubEventNormalizerTest extends TestCase
{
    private SubEventNormalizer $normalizer;

    private SubEvent $subEvent;

    protected function setUp(): void
    {
        $this->normalizer = new SubEventNormalizer();

        $this->subEvent = new SubEvent(
            new DateRange(
                new DateTimeImmutable('2021-05-17T16:00:00+00:00'),
                new DateTimeImmutable('2021-05-17T22:00:00+00:00')
            ),
            new Status(StatusType::Available()),
            new BookingAvailability(BookingAvailabilityType::Available()),
            new BookingInfo()
        );
    }

}
