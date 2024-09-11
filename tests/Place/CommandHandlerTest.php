<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
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
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $address = new Address(
            new Street('Kerkstraat 69'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );
        $calendar = new Calendar(CalendarType::PERMANENT());

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
            ->then([new AddressUpdated($id, $updatedAddress)]);
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
            ->then([new AddressTranslated($id, $updatedAddress, new Language('fr'))]);
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
            new EventType('0.50.4.0.0', 'concert'),
            new Address(
                new Street('Kerkstraat 69'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
