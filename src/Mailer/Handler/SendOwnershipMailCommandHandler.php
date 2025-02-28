<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Mailer\Handler\Helper\OwnershipMailParamExtractor;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\RecipientStrategy;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\SendToRequesterOfOwnership;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\UserIdentityDetails;
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
    private UserIdentityResolver $identityResolver;
    private TwigEnvironment $twig;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private OwnershipMailParamExtractor $paramExtractor;
    private LoggerInterface $logger;

    public function __construct(
        Mailer $mailer,
        MailsSentRepository $mailsSentRepository,
        UserIdentityResolver $identityResolver,
        TwigEnvironment $twig,
        OwnershipSearchRepository $ownershipSearchRepository,
        OwnershipMailParamExtractor $paramExtractor,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->mailsSentRepository = $mailsSentRepository;
        $this->identityResolver = $identityResolver;
        $this->twig = $twig;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->paramExtractor = $paramExtractor;
        $this->logger = $logger;
    }

    public function handle($command): void
    {
        switch (true) {
            case $command instanceof SendOwnershipRequestedMail:
                $this->processCommand(
                    $command,
                    self::SUBJECT_OWNERSHIP_REQUESTED,
                    self::TEMPLATE_OWNERSHIP_REQUESTED,
                    new SendToRequesterOfOwnership($this->identityResolver, $this->logger)
                );
                break;
        }
    }

    public function processCommand(SendOwnershipRequestedMail $command, string $rawSubject, string $template, RecipientStrategy $recipients): void
    {
        $uuid = new Uuid($command->getUuid());

        if ($this->mailsSentRepository->isMailSent($uuid, get_class($command))) {
            $this->logger->info(sprintf('[ownership-mail] Mail %s about %s was already sent', $uuid->toString(), get_class($command)));
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

        /** @var UserIdentityDetails $userIdentityDetails */
        foreach ($recipients->getRecipients($ownershipItem, $organizer) as $userIdentityDetails) {
            $this->sendMail(
                $organizer['name']['nl'],
                $userIdentityDetails,
                $ownershipItem,
                $rawSubject,
                $template,
                $command
            );
        }
    }

        try {
            $params = $this->paramExtractor->fetchParams($ownershipItem, $ownerDetails);
        } catch (DocumentDoesNotExist $e) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load organizer: %s', $e->getMessage()));
            return;
        }

        $subject = $this->parseSubject($rawSubject, $params['organisationName']);
        $to = new EmailAddress($ownerDetails->getEmailAddress());
    public function sendMail(
        string $organisationName,
        UserIdentityDetails $userIdentityDetails,
        OwnershipItem $ownershipItem,
        string $rawSubject,
        string $template,
        SendOwnershipRequestedMail $command
    ): void {
        $params = [
            'organisationName' => $organisationName,
            'firstName' => $userIdentityDetails->getUserName(),
            'organisationUrl' => $this->organizerIriGenerator->iri($ownershipItem->getItemId()),
        ];

        $subject = $this->parseSubject($rawSubject, $organisationName);
        $to = new EmailAddress($userIdentityDetails->getEmailAddress());

        try {
            $success = $this->mailer->send(
                $to,
                $subject,
                $this->twig->render($template . '.html.twig', $params),
                $this->twig->render($template . '.txt.twig', $params),
            );
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->logger->error('[ownership-mail] ' . $e->getMessage());
            return;
        }

        if (!$success) {
            $this->logger->error(sprintf('[ownership-mail] Mail "%s" failed to sent to %s', $subject, $to->toString()));
            return;
        }

        $this->mailsSentRepository->addMailSent(new Uuid($ownershipItem->getId()), $to, get_class($command), new DateTimeImmutable());

        $this->logger->info(sprintf('[ownership-mail] Mail "%s" sent to %s', $subject, $to->toString()));
    }

    private function parseSubject(string $subject, string $name): string
    {
        return str_replace('{{ organisationName }}', $name, $subject);
    }
}
