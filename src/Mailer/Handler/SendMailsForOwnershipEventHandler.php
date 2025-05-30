<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;
use CultuurNet\UDB3\CommandHandling\ContextDecoratedCommandBus;
use CultuurNet\UDB3\Http\AsyncDispatchTrait;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipAcceptedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRejectedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;

final class SendMailsForOwnershipEventHandler implements EventListener
{
    use AsyncDispatchTrait;

    private ContextDecoratedCommandBus $mailerCommandBus;

    private DomainMessageSpecificationInterface $isReplay;

    private DomainMessageSpecificationInterface $mailsDisabled;

    public function __construct(
        ContextDecoratedCommandBus $mailerCommandBus,
        DomainMessageSpecificationInterface $isReplay,
        DomainMessageSpecificationInterface $mailsDisabled
    ) {
        $this->mailerCommandBus = $mailerCommandBus;
        $this->isReplay = $isReplay;
        $this->mailsDisabled = $mailsDisabled;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if (
            $this->isReplay->isSatisfiedBy($domainMessage) ||
            $this->mailsDisabled->isSatisfiedBy($domainMessage)) {
            return;
        }

        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof OwnershipRequested:
                $this->dispatchAsyncCommand($this->mailerCommandBus, new SendOwnershipRequestedMail($event->getId()));
                break;
            case $event instanceof OwnershipApproved:
                $this->dispatchAsyncCommand($this->mailerCommandBus, new SendOwnershipAcceptedMail($event->getId()));
                break;
            case $event instanceof OwnershipRejected:
                $this->dispatchAsyncCommand($this->mailerCommandBus, new SendOwnershipRejectedMail($event->getId()));
                break;
        }
    }
}
