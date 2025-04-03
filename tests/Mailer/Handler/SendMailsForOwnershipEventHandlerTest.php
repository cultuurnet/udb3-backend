<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;
use CultuurNet\UDB3\CommandHandling\ContextDecoratedCommandBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Mailer\Command\AbstractSendOwnershipMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipAcceptedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRejectedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionUpdated;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Broadway\Serializer\Serializable;

class SendMailsForOwnershipEventHandlerTest extends TestCase
{
    private const DATE_TIME_VALUE = '2025-1-1T12:30:00+00:00';

    private SendMailsForOwnershipEventHandler $sendMailsForOwnership;

    /** @var DomainMessageSpecificationInterface|MockObject */
    private $domainMessageSpecification;

    /** @var ContextDecoratedCommandBus|MockObject */
    private $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(ContextDecoratedCommandBus::class);
        $this->domainMessageSpecification = $this->createMock(DomainMessageSpecificationInterface::class);
        $this->sendMailsForOwnership = new SendMailsForOwnershipEventHandler(
            $this->commandBus,
            $this->domainMessageSpecification,
        );
    }

    /**
     * @test
     * @dataProvider eventWithCorrespondingCommandProvider
     * This is the happy path
     * */
    public function it_converts_the_event_to_the_correct_command(Serializable $event, AbstractSendOwnershipMail $command): void
    {
        $domainMessage = (new DomainMessageBuilder())->create($event);

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(false);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    public function eventWithCorrespondingCommandProvider(): array
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        return [
            'OwnershipRequested' => [
                new OwnershipRequested(
                    $id,
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
                new SendOwnershipRequestedMail($id),
            ],
            'OwnershipApproved' => [
                new OwnershipApproved($id),
                new SendOwnershipAcceptedMail($id),
            ],
            'OwnershipRejected' => [
                new OwnershipRejected($id),
                new SendOwnershipRejectedMail($id),
            ],
        ];
    }

    /** @test */
    public function it_blocks_replays(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $organizerId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $domainMessage = (new DomainMessageBuilder())
            ->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)
            ->create($this->givenAnOwnershipRequested($id, $organizerId, $ownerId));

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(true);

        $this->commandBus->expects($this->never())->method('dispatch');

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    /** @test */
    public function it_does_not_dispatch_with_an_invalid_event(): void
    {
        $domainMessage = (new DomainMessageBuilder())
            ->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)
            ->create(new DescriptionUpdated(
                'event-123',
                new Description('description-456')
            ));

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(true);

        $this->commandBus->expects($this->never())->method('dispatch');

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    private function givenAnOwnershipRequested(string $id, string $organizerId, string $ownerId): OwnershipRequested
    {
        return new OwnershipRequested(
            $id,
            $organizerId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
    }
}
