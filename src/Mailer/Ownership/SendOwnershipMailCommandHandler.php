<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\UserIdentityResolver;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SendOwnershipMailCommandHandler implements CommandHandler
{
    private const SUBJECT_OWNERSHIP_REQUESTED = 'Beheers aanvraag voor organisatie {{ organisationName }}';
    private const TEMPLATE_OWNERSHIP_REQUESTED = 'ownershipRequested';

    private Mailer $mailer;
    private MailsSentRepository $mailsSentRepository;
    private LoggerInterface $logger;
    private DocumentRepository $organizerRepository;
    private UserIdentityResolver $identityResolver;
    private IriGeneratorInterface $organizerIriGenerator;
    private TwigEnvironment $twig;
    private OwnershipSearchRepository $ownershipSearchRepository;

    public function __construct(
        Mailer $mailer,
        MailsSentRepository $mailsSentRepository,
        DocumentRepository $organizerRepository,
        UserIdentityResolver $identityResolver,
        IriGeneratorInterface $organizerIriGenerator,
        TwigEnvironment $twig,
        OwnershipSearchRepository $ownershipSearchRepository,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->mailsSentRepository = $mailsSentRepository;
        $this->logger = $logger;
        $this->organizerRepository = $organizerRepository;
        $this->identityResolver = $identityResolver;
        $this->organizerIriGenerator = $organizerIriGenerator;
        $this->twig = $twig;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
    }

    public function handle($command): void
    {
        switch (true) {
            case $command instanceof SendOwnershipRequestedMail:
                $this->sentMail(
                    $command,
                    self::SUBJECT_OWNERSHIP_REQUESTED,
                    self::TEMPLATE_OWNERSHIP_REQUESTED
                );
                break;

        }
    }

    public function sentMail(SendOwnershipRequestedMail $command, string $rawSubject, string $template): void
    {
        $uuid = new Uuid($command->getUuid());

        if ($this->mailsSentRepository->isMailSent($uuid, get_class($command))) {
            return;
        }

        try {
            $ownershipItem = $this->ownershipSearchRepository->getById($uuid->toString());
        } catch (OwnershipItemNotFound $e) {
            $this->logger->warning('[ownership-mail] ' . $e->getMessage());
            return;
        }

        try {
            $organizerProjection = $this->organizerRepository->fetch($ownershipItem->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load organizer: %s', $e->getMessage()));
            return;
        }

        $organizer = $organizerProjection->getAssocBody();

        //@todo loop over ALL owners of organisation
        $ownerId = $ownershipItem->getOwnerId();
        $ownerDetails = $this->identityResolver->getUserById($ownerId);

        if ($ownerDetails === null) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load owner details for %s', $ownerId));
            return;
        }

        $params = [
            'organisationName' => $organizer['name']['nl'],
            'firstName' => $ownerDetails->getUserName(),//@todo change to be the correct user
            'organisationUrl' => $this->organizerIriGenerator->iri($ownershipItem->getItemId()),
        ];

        $subject = $this->parseSubject($rawSubject, $organizer['name']['nl']);
        $to = new EmailAddress($ownerDetails->getEmailAddress());

        try {
            $success = $this->mailer->send(
                $to,
                $subject,
                $this->twig->render($template . '.html.twig', $params),
                $this->twig->render($template . '.txt.twig', $params),
            );
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        if (!$success) {
            return;
        }

        $this->mailsSentRepository->addMailSent($uuid, $to, get_class($command), new DateTimeImmutable());

        $this->logger->info(sprintf('Mail "%s" sent to %s', $subject, $to->toString()));
    }

    private function parseSubject(string $subject, string $name): string
    {
        return str_replace('{{ organisationName }}', $name, $subject);
    }
}
