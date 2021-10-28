<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Language as LegacyLanguage;
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
            Country::fromNative('BE')
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
            ->then([new AddressUpdated($id, $updatedAddress)]);
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
            Country::fromNative('FR')
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
            ->then([new AddressTranslated($id, $updatedAddress, new LegacyLanguage('fr'))]);
    }

    private function organizerCreated(string $id): OrganizerCreated
    {
        return new OrganizerCreated(
            $id,
            new Title('Organizer Title'),
            [
                new Address(
                    new Street('Kerkstraat 69'),
                    new PostalCode('9630'),
                    new Locality('Zottegem'),
                    Country::fromNative('BE')
                ),
            ],
            ['phone'],
            ['email'],
            ['url']
        );
    }
}
