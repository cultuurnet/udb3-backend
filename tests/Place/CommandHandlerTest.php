<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\Locality as LegacyLocality;
use CultuurNet\UDB3\Address\PostalCode as LegacyPostalCode;
use CultuurNet\UDB3\Address\Street as LegacyStreet;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Media\MediaManagerInterface;
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
use CultuurNet\UDB3\OfferCommandHandlerTestTrait;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class CommandHandlerTest extends CommandHandlerScenarioTestCase
{
    use OfferCommandHandlerTestTrait;

    /**
     * @test
     */
    public function it_can_update_major_info_of_a_place(): void
    {
        $id = '1';
        $title = new Title('foo');
        $eventType = new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType());
        $address = new LegacyAddress(
            new LegacyStreet('Kerkstraat 69'),
            new LegacyPostalCode('3000'),
            new LegacyLocality('Leuven'),
            new CountryCode('BE')
        );
        $calendar = new Calendar(CalendarType::permanent());

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new UpdateMajorInfo($id, $title, $eventType, $address, $calendar)
            )
            ->then([new MajorInfoUpdated($id, $title->toString(), $eventType, $address, $calendar)]);
    }

    /**
     * @test
     * @dataProvider updateAddressDataProvider
     *
     */
    public function it_should_handle_an_update_address_command_for_the_main_language(
        Address $updatedAddress
    ): void {
        $id = '45b9e456-f5d6-4b5c-b692-a4bb22b88332';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->factorOfferCreated($id)])
            ->when(
                new UpdateAddress($id, $updatedAddress, new Language('nl'))
            )
            ->then([new AddressUpdated($id, LegacyAddress::fromUdb3ModelAddress($updatedAddress))]);
    }

    /**
     * @test
     * @dataProvider updateAddressDataProvider
     *
     */
    public function it_should_handle_an_update_address_command_for_any_language_other_than_the_language(
        Address $updatedAddress
    ): void {
        $id = '45b9e456-f5d6-4b5c-b692-a4bb22b88332';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->factorOfferCreated($id)])
            ->when(
                new UpdateAddress($id, $updatedAddress, new Language('fr'))
            )
            ->then([new AddressTranslated($id, LegacyAddress::fromUdb3ModelAddress($updatedAddress), new Language('fr'))]);
    }

    public function updateAddressDataProvider(): array
    {
        return [
            [
                'updated' => new Address(
                    new Street('Eenmeilaan 35'),
                    new PostalCode('3010'),
                    new Locality('Kessel-Lo'),
                    new CountryCode('BE')
                ),
            ],
        ];
    }

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): CommandHandler {
        $repository = new PlaceRepository(
            $eventStore,
            $eventBus
        );

        $this->organizerRepository = $this->createMock(Repository::class);

        $this->mediaManager = $this->createMock(MediaManagerInterface::class);

        return new CommandHandler(
            $repository,
            $this->organizerRepository,
            $this->mediaManager
        );
    }

    private function factorOfferCreated(string $id): PlaceCreated
    {
        return new PlaceCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new EventType('0.50.4.0.0', 'Concert'),
            new LegacyAddress(
                new LegacyStreet('Kerkstraat 69'),
                new LegacyPostalCode('3000'),
                new LegacyLocality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(CalendarType::permanent())
        );
    }
}
