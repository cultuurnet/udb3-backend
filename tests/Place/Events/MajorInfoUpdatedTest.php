<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use PHPUnit\Framework\TestCase;

final class MajorInfoUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_to_granular_events(): void
    {
        $placeId = 'e564408b-99ac-4dfc-90fd-e71496e8c845';

        $event = new MajorInfoUpdated(
            $placeId,
            'Title',
            new EventType('0.14.0.0.0', 'Monument'),
            new Address(
                new Street('Martelarenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(CalendarType::permanent())
        );

        $expected = [
            new TitleUpdated($placeId, 'Title'),
            new TypeUpdated($placeId, new EventType('0.14.0.0.0', 'Monument')),
            new AddressUpdated(
                $placeId,
                new Address(
                    new Street('Martelarenlaan 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    new CountryCode('BE')
                )
            ),
            new CalendarUpdated($placeId, new Calendar(CalendarType::permanent())),
        ];

        $actual = $event->toGranularEvents();

        $this->assertEquals($expected, $actual);
    }
}
