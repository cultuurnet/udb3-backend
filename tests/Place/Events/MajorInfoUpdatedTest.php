<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
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
            new Category(new CategoryID('0.14.0.0.0'), new CategoryLabel('Monument'), CategoryDomain::eventType()),
            new Address(
                new Street('Martelarenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new PermanentCalendar(new OpeningHours())
        );

        $expected = [
            new TitleUpdated($placeId, 'Title'),
            new TypeUpdated(
                $placeId,
                new Category(new CategoryID('0.14.0.0.0'), new CategoryLabel('Monument'), CategoryDomain::eventType())
            ),
            new AddressUpdated(
                $placeId,
                new Address(
                    new Street('Martelarenlaan 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    new CountryCode('BE')
                )
            ),
            new CalendarUpdated($placeId, new PermanentCalendar(new OpeningHours())),
        ];

        $actual = $event->toGranularEvents();

        $this->assertEquals($expected, $actual);
    }
}
