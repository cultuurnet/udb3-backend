<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\EventHandling\EventBus;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\EventBus\TraceableEventBus;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\ProjectedToJSONLDFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BroadcastingContributorRepositoryTest extends TestCase
{
    /**
     * @var ContributorRepository&MockObject
     */
    private $decoratee;

    private TraceableEventBus $eventBus;

    private BroadcastingContributorRepository $contributorRepository;

    private Uuid $itemId;

    private EmailAddress $email;

    private EmailAddresses $emails;

    protected function setUp(): void
    {
        $this->itemId = new Uuid('f28b47d1-4d06-4c46-94cc-d0ddbaad102f');
        $this->decoratee = $this->createMock(ContributorRepository::class);
        $this->eventBus = new TraceableEventBus($this->createMock(EventBus::class));
        $this->contributorRepository = new BroadcastingContributorRepository(
            $this->decoratee,
            $this->eventBus,
            new ProjectedToJSONLDFactory(
                new CallableIriGenerator(
                    fn ($cdbid) => 'https://io.uitdatabank.dev/events/' . $cdbid
                ),
                new CallableIriGenerator(
                    fn ($cdbid) =>  'https://io.uitdatabank.dev/places/' . $cdbid
                ),
                new CallableIriGenerator(
                    fn ($cdbid) => 'https://io.uitdatabank.dev/organizers/' . $cdbid
                )
            )
        );

        $this->email = new EmailAddress('foo@bar.com');
        $this->emails = EmailAddresses::fromArray([$this->email]);

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
     */
    public function it_can_check_if_an_email_is_a_contributor(): void
    {
        $this->decoratee->expects($this->once())
            ->method('isContributor')
            ->with($this->itemId, $this->email)
            ->willReturn(true);

        $this->assertTrue($this->contributorRepository->isContributor($this->itemId, $this->email));
    }

    /**
     * @test
     * @dataProvider contributorsUpdatedProvider
     */
    public function it_will_publish_when_contributors_are_updated(ItemType $itemType, Serializable $contributorsUpdated): void
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
                new EventProjectedToJSONLD(
                    'f28b47d1-4d06-4c46-94cc-d0ddbaad102f',
                    'https://io.uitdatabank.dev/events/f28b47d1-4d06-4c46-94cc-d0ddbaad102f'
                ),
            ],
            'place' => [
                ItemType::place(),
                new PlaceProjectedToJSONLD(
                    'f28b47d1-4d06-4c46-94cc-d0ddbaad102f',
                    'https://io.uitdatabank.dev/places/f28b47d1-4d06-4c46-94cc-d0ddbaad102f'
                ),
            ],
            'organizer' => [
                ItemType::organizer(),
                new OrganizerProjectedToJSONLD(
                    'f28b47d1-4d06-4c46-94cc-d0ddbaad102f',
                    'https://io.uitdatabank.dev/organizers/f28b47d1-4d06-4c46-94cc-d0ddbaad102f'
                ),
            ],
        ];
    }
}
