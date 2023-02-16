<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Event\EventContributorsUpdated;
use CultuurNet\UDB3\EventBus\TraceableEventBus;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Organizer\OrganizerContributorsUpdated;
use CultuurNet\UDB3\Place\PlaceContributorsUpdated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BroadcastingContributorRepositoryTest extends TestCase
{
    /**
     * @var ContributorRepository|MockObject
     */
    private $decoratee;

    private TraceableEventBus $eventBus;

    private BroadcastingContributorRepository $contributorRepository;

    private UUID $itemId;

    private EmailAddresses $emails;

    protected function setUp(): void
    {
        $this->itemId = new UUID('f28b47d1-4d06-4c46-94cc-d0ddbaad102f');
        $this->decoratee = $this->createMock(ContributorRepository::class);
        $this->eventBus = new TraceableEventBus($this->createMock(EventBus::class));
        $this->contributorRepository = new BroadcastingContributorRepository(
            $this->decoratee,
            $this->eventBus,
            new ContributorsUpdatedFactory(
                new CallableIriGenerator(
                    fn ($cdbid) => 'https://io.uitdatabank.dev/events/' . $cdbid . '/contributors'
                ),
                new CallableIriGenerator(
                    fn ($cdbid) =>  'https://io.uitdatabank.dev/places/' . $cdbid . '/contributors'
                ),
                new CallableIriGenerator(
                    fn ($cdbid) => 'https://io.uitdatabank.dev/organizers/' . $cdbid . '/contributors'
                )
            )
        );

        $this->emails = EmailAddresses::fromArray([new EmailAddress('foo@bar.com')]);

        $this->eventBus->trace();
    }

    /**
     * @test
     */
    public function it_can_get_contributors(): void
    {
        $this->decoratee->expects($this->once())
            ->method('getContributors')
            ->with($this->itemId)
            ->willReturn($this->emails);

        $result = $this->contributorRepository->getContributors($this->itemId);

        $this->assertEquals($this->emails, $result);
    }

    /**
     * @test
     * @dataProvider contributorsUpdatedProvider
     */
    public function it_will_publish_when_contributors_are_updated(ItemType $itemType, ContributorsUpdated $contributorsUpdated): void
    {
        $this->decoratee->expects($this->once())
            ->method('updateContributors')
            ->with($this->itemId, $this->emails, $itemType);

        $this->contributorRepository->updateContributors($this->itemId, $this->emails, $itemType);

        $expected = [$contributorsUpdated];
        $actual = $this->eventBus->getEvents();

        $this->assertEquals($expected, $actual);
    }

    public function contributorsUpdatedProvider(): array
    {
        return [
            'event' => [
                ItemType::event(),
                new EventContributorsUpdated(
                    'f28b47d1-4d06-4c46-94cc-d0ddbaad102f',
                    'https://io.uitdatabank.dev/events/f28b47d1-4d06-4c46-94cc-d0ddbaad102f/contributors'
                ),
            ],
            'place' => [
                ItemType::place(),
                new PlaceContributorsUpdated(
                    'f28b47d1-4d06-4c46-94cc-d0ddbaad102f',
                    'https://io.uitdatabank.dev/places/f28b47d1-4d06-4c46-94cc-d0ddbaad102f/contributors'
                ),
            ],
            'organizer' => [
                ItemType::organizer(),
                new OrganizerContributorsUpdated(
                    'f28b47d1-4d06-4c46-94cc-d0ddbaad102f',
                    'https://io.uitdatabank.dev/organizers/f28b47d1-4d06-4c46-94cc-d0ddbaad102f/contributors'
                ),
            ],
        ];
    }
}
