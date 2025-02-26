<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;
use CultuurNet\UDB3\CommandHandling\ContextDecoratedCommandBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    /** @test
     * This is the happy path
     * */
    public function it_handles_ownership_requested_event(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $organizerId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $event = new OwnershipRequested(
            $id,
            $organizerId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
        $domainMessage = (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($event);

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(false);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new SendOwnershipRequestedMail($id));

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    /** @test */
    public function it_blocks_replays(): void
    {
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $event = $this->getEvent($itemId, $ownerId);
        $domainMessage = (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($event);

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

    private function getEvent(string $itemId, string $ownerId): OwnershipRequested
    {
        return new OwnershipRequested(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $itemId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
    }
}
