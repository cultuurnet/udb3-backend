<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\Locality as LegacyLocality;
use CultuurNet\UDB3\Address\PostalCode as LegacyPostalCode;
use CultuurNet\UDB3\Address\Street as LegacyStreet;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use ValueObjects\Geography\Country;

class UpdateAddressHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateAddressHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_address_in_default_language_nl(): void
    {
        $id = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $updatedAddress = new Address(
            new Street('Nieuwstraat 3'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(
                new UpdateAddress(
                    $id,
                    $updatedAddress,
                    new Language('nl')
                )
            )
            ->then([
                new AddressUpdated(
                    $id,
                    $updatedAddress->getStreet()->toString(),
                    $updatedAddress->getPostalCode()->toString(),
                    $updatedAddress->getLocality()->toString(),
                    $updatedAddress->getCountryCode()->toString()
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_updating_address_in_non_default_language(): void
    {
        $id = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $updatedAddress = new Address(
            new Street('Rue de l\'Église'),
            new PostalCode('1000-FR'),
            new Locality('Paris'),
            new CountryCode('FR')
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(
                new UpdateAddress(
                    $id,
                    $updatedAddress,
                    new Language('fr')
                )
            )
            ->then([
                new AddressTranslated(
                    $id,
                    $updatedAddress->getStreet()->toString(),
                    $updatedAddress->getPostalCode()->toString(),
                    $updatedAddress->getLocality()->toString(),
                    $updatedAddress->getCountryCode()->toString(),
                    'fr'
                ),
            ]);
    }

    private function organizerCreated(string $id): OrganizerCreated
    {
        return new OrganizerCreated(
            $id,
            'Organizer Title',
            [
                new LegacyAddress(
                    new LegacyStreet('Kerkstraat 69'),
                    new LegacyPostalCode('9630'),
                    new LegacyLocality('Zottegem'),
                    Country::fromNative('BE')
                ),
            ],
            ['phone'],
            ['email'],
            ['url']
        );
    }
}
