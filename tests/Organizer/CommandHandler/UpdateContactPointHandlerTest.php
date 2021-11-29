<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\ContactPoint as LegacyContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url as LegacyUrl;

class UpdateContactPointHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateContactPointHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_the_contact_point(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            $id,
            'nl',
            'https://www.madewithlove.be',
            'Organizer Title'
        );

        $contactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('016 10 20 30')),
            new EmailAddresses(new EmailAddress('info@publiq.be')),
            new Urls(new Url('https://www.publiq.be'))
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$organizerCreated])
            ->when(new UpdateContactPoint($id, $contactPoint))
            ->then([
                new ContactPointUpdated(
                    $id,
                    LegacyContactPoint::fromUdb3ModelContactPoint($contactPoint)
                ),
            ]);
    }
}
