<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

class RemoveAddressHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new RemoveAddressHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_deleting_an_address(): void
    {
        $id = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreated($id),
                new AddressUpdated(
                    $id,
                    'Kerkstraat 10',
                    '1000',
                    'Brussel',
                    'BE'
                ),
            ])
            ->when(new RemoveAddress($id))
            ->then([new AddressRemoved($id)]);
    }

    /**
     * @test
     */
    public function it_only_deletes_when_an_address_is_present(): void
    {
        $id = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(new RemoveAddress($id))
            ->then([]);
    }

    private function organizerCreated($id): OrganizerCreatedWithUniqueWebsite
    {
        return new OrganizerCreatedWithUniqueWebsite(
            $id,
            'nl',
            'https://www.madewithlove.be',
            'Organizer Title'
        );
    }
}
