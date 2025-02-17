<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\UserIdentityResolver;
use DateTimeInterface;
use Psr\Log\LoggerInterface;

final class SendMailsForOwnership implements EventListener
{
    private const SUBJECT_OWNERSHIP_REQUESTED = 'Beheers aanvraag voor organisatie {{ organisationName }}';

    private DomainMessageSpecificationInterface $isReplay;
    private Mailer $mailer;
    private DocumentRepository $organizerRepository;
    private UserIdentityResolver $identityResolver;
    private IriGeneratorInterface $organizerIriGenerator;
    private MailsSentRepository $mailsSentRepository;
    private LoggerInterface $logger;
    private bool $sendOrganiserMail;

    public function __construct(
        DomainMessageSpecificationInterface $domainMessageSpecification,
        Mailer $mailer,
        DocumentRepository $organizerRepository,
        UserIdentityResolver $identityResolver,
        IriGeneratorInterface $organizerIriGenerator,
        MailsSentRepository $mailsSentRepository,
        LoggerInterface $logger,
        bool $sendOrganiserMail
    ) {
        $this->isReplay = $domainMessageSpecification;
        $this->mailer = $mailer;
        $this->organizerRepository = $organizerRepository;
        $this->identityResolver = $identityResolver;
        $this->logger = $logger;
        $this->organizerIriGenerator = $organizerIriGenerator;
        $this->mailsSentRepository = $mailsSentRepository;
        $this->sendOrganiserMail = $sendOrganiserMail;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if (!$this->sendOrganiserMail) {
            return;
        }

        if ($this->isReplay->isSatisfiedBy($domainMessage)) {
            // This is a replay, don't sent the email
            return;
        }

        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof OwnershipRequested:
                $this->handleOwnershipRequested($event, $domainMessage->getRecordedOn()->toNative());
                break;
            case $event instanceof OwnershipApproved:
                // @Todo
                break;
            case $event instanceof OwnershipRejected:
                // @Todo
                break;
        }
    }

    private function handleOwnershipRequested(OwnershipRequested $ownershipRequested, DateTimeInterface $dateTime): void
    {
        if ($this->mailsSentRepository->isMailSent(new Uuid($ownershipRequested->getId()), OwnershipRequested::class)) {
            return;
        }

        try {
            $organizerProjection = $this->organizerRepository->fetch($ownershipRequested->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load organizer: %s', $e->getMessage()));
            return;
        }

        $organizer = $organizerProjection->getAssocBody();

        $ownerId = $ownershipRequested->getOwnerId();
        $ownerDetails = $this->identityResolver->getUserById($ownerId);

        if ($ownerDetails === null) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load owner details for %s', $ownerId));
            return;
        }

        $to = new EmailAddress($ownerDetails->getEmailAddress());
        $subject = $this->parseSubject($organizer['name']['nl']);

        $success = $this->mailer->send(
            $to,
            $subject,
            'ownershipRequested.html.twig',
            'ownershipRequested.txt.twig',
            [
                'organisationName' => $organizer['name']['nl'],
                'firstName' => $ownerDetails->getUserName(),
                'organisationUrl' => $this->organizerIriGenerator->iri($ownershipRequested->getItemId()),
            ]
        );

        if (!$success) {
            return;
        }

        $this->mailsSentRepository->addMailSent(new Uuid($ownershipRequested->getId()), $to, OwnershipRequested::class, $dateTime);

        $this->logger->info(sprintf('Mail "%s" sent to %s', $subject, $ownerDetails->getEmailAddress()));
    }

    private function parseSubject(string $name): string
    {
        return str_replace('{{ organisationName }}', $name, self::SUBJECT_OWNERSHIP_REQUESTED);
    }
}
