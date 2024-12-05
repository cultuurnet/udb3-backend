<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use Psr\Log\LoggerInterface;

class ExtendedGeoCoordinatesCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    private const PLACE_ID = 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): ExtendedGeoCoordinatesCommandHandler
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Handling %s command for place with id: %s',
                    UpdateGeoCoordinatesFromAddress::class,
                    self::PLACE_ID
                )
            );

        return new ExtendedGeoCoordinatesCommandHandler(
            $logger
        );
    }

    /**
     * @test
     */
    public function it_logs_calls_to_update_geo_coords_from_address(): void
    {
        $address = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );

        $placeCreated = new PlaceCreated(
            self::PLACE_ID,
            new Language('en'),
            'Some place',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            LegacyAddress::fromUdb3ModelAddress($address),
            new Calendar(CalendarType::permanent())
        );

        $command = new UpdateGeoCoordinatesFromAddress(self::PLACE_ID, $address);

        $this->scenario
            ->withAggregateId(self::PLACE_ID)
            ->given([$placeCreated])
            ->when($command);
    }
}
