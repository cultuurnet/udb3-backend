<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;
use CultuurNet\UDB3\CommandHandling\ContextDecoratedCommandBus;
use CultuurNet\UDB3\Http\AsyncDispatchTrait;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Mailer\Command\SentOwnershipMail;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class SendMailsForOwnership implements EventListener
{
    use AsyncDispatchTrait;

    private const SUBJECT_OWNERSHIP_REQUESTED = 'Beheers aanvraag voor organisatie {{ organisationName }}';

    private ContextDecoratedCommandBus $mailerCommandBus;
    private DomainMessageSpecificationInterface $isReplay;
    private DocumentRepository $organizerRepository;
    private UserIdentityResolver $identityResolver;
    private IriGeneratorInterface $organizerIriGenerator;
    private TwigEnvironment $twig;
    private LoggerInterface $logger;
    private bool $sendOrganiserMail;

    public function __construct(
        ContextDecoratedCommandBus $mailerCommandBus,
        DomainMessageSpecificationInterface $domainMessageSpecification,
        DocumentRepository $organizerRepository,
        UserIdentityResolver $identityResolver,
        IriGeneratorInterface $organizerIriGenerator,
        TwigEnvironment $twig,
        LoggerInterface $logger,
        bool $sendOrganiserMail
    ) {
        $this->mailerCommandBus = $mailerCommandBus;
        $this->isReplay = $domainMessageSpecification;
        $this->organizerRepository = $organizerRepository;
        $this->identityResolver = $identityResolver;
        $this->organizerIriGenerator = $organizerIriGenerator;
        $this->twig = $twig;
        $this->logger = $logger;
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
                $this->handleOwnershipRequested($event);
                break;
            case $event instanceof OwnershipApproved:
                // @Todo
                break;
            case $event instanceof OwnershipRejected:
                // @Todo
                break;
        }
    }

    private function handleOwnershipRequested(OwnershipRequested $ownershipRequested): void
    {
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

        $params = [
            'organisationName' => $organizer['name']['nl'],
            'firstName' => $ownerDetails->getUserName(),
            'organisationUrl' => $this->organizerIriGenerator->iri($ownershipRequested->getItemId()),
        ];

        $subject = $this->parseSubject(self::SUBJECT_OWNERSHIP_REQUESTED, $organizer['name']['nl']);

        try {
            $commandId = $this->dispatchAsyncCommand($this->mailerCommandBus, new SentOwnershipMail(
                new Uuid($ownershipRequested->getId()),
                new EmailAddress($ownerDetails->getEmailAddress()),
                $subject,
                $this->twig->render('ownershipRequested.html.twig', $params),
                $this->twig->render('ownershipRequested.txt.twig', $params),
            ));
        } catch (CommandAuthorizationException|LoaderError|RuntimeError|SyntaxError $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $this->logger->info(sprintf('[ownership-mail] [%s] Queue mail %s to %s', $commandId, $subject, $ownerDetails->getEmailAddress()));
    }

    private function parseSubject(string $subject, string $name): string
    {
        return str_replace('{{ organisationName }}', $name, $subject);
    }
}
