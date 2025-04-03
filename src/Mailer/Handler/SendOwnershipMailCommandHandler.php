<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Mailer\Command\AbstractSendOwnershipMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipAcceptedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRejectedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Mailer\Handler\Helper\OwnershipMailParamExtractor;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\RecipientStrategy;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\User\UserIdentityDetails;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class SendOwnershipMailCommandHandler implements CommandHandler
{
    private const SUBJECT_OWNERSHIP_REQUESTED = 'Beheers aanvraag voor organisatie {{ organisationName }}';
    private const TEMPLATE_OWNERSHIP_REQUESTED = 'ownership/requested';

    private const SUBJECT_OWNERSHIP_APPROVED = 'Je bent nu beheerder van organisatie {{ organisationName }}!';
    private const TEMPLATE_OWNERSHIP_APPROVED = 'ownership/approved';

    private const SUBJECT_OWNERSHIP_REJECTED = 'Je beheersaanvraag voor organisatie {{ organisationName }} is geweigerd';
    private const TEMPLATE_OWNERSHIP_REJECTED = 'ownership/rejected';

    private Mailer $mailer;
    private TwigEnvironment $twig;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private OwnershipMailParamExtractor $paramExtractor;
    private RecipientStrategy $sendToOwnersOfOrganizer;
    private RecipientStrategy $sendToOwnerOfOwnership;
    private LoggerInterface $logger;

    public function __construct(
        Mailer $mailer,
        TwigEnvironment $twig,
        OwnershipSearchRepository $ownershipSearchRepository,
        OwnershipMailParamExtractor $paramExtractor,
        RecipientStrategy $sendToOwnersAndCreatorOfOrganisation,
        RecipientStrategy $sendToOwnerOfOwnership,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->paramExtractor = $paramExtractor;
        $this->sendToOwnersOfOrganizer = $sendToOwnersAndCreatorOfOrganisation;
        $this->sendToOwnerOfOwnership = $sendToOwnerOfOwnership;
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
                    $this->sendToOwnersOfOrganizer,
                );
                break;
            case $command instanceof SendOwnershipAcceptedMail:
                $this->processCommand(
                    $command,
                    self::SUBJECT_OWNERSHIP_APPROVED,
                    self::TEMPLATE_OWNERSHIP_APPROVED,
                    $this->sendToOwnerOfOwnership,
                );
                break;
            case $command instanceof SendOwnershipRejectedMail:
                $this->processCommand(
                    $command,
                    self::SUBJECT_OWNERSHIP_REJECTED,
                    self::TEMPLATE_OWNERSHIP_REJECTED,
                    $this->sendToOwnerOfOwnership,
                );
                break;
        }
    }

    private function processCommand(AbstractSendOwnershipMail $command, string $rawSubject, string $template, RecipientStrategy $recipientStrategy): void
    {
        $uuid = new Uuid($command->getUuid());

        try {
            $ownershipItem = $this->ownershipSearchRepository->getById($uuid->toString());
        } catch (OwnershipItemNotFound $e) {
            $this->logger->warning('[ownership-mail] ' . $e->getMessage());
            return;
        }

        $recipients = $recipientStrategy->getRecipients($ownershipItem);

        if (!count($recipients->getRecipients())) {
            $this->logger->warning(sprintf('[ownership-mail] No recipients found to send mail for ownership %s', $command->getUuid()));
        }

        foreach ($recipients as $userIdentityDetails) {
            $this->sendMail(
                $command,
                $userIdentityDetails,
                $ownershipItem,
                $rawSubject,
                $template,
            );
        }
    }

    public function sendMail(
        AbstractSendOwnershipMail $command,
        UserIdentityDetails $userIdentityDetails,
        OwnershipItem $ownershipItem,
        string $rawSubject,
        string $template
    ): void {
        try {
            $params = $this->paramExtractor->fetchParams($ownershipItem, $userIdentityDetails);
        } catch (DocumentDoesNotExist $e) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load organizer: %s', $e->getMessage()));
            return;
        }

        $subject = $this->parseSubject($rawSubject, $params['organisationName']);
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

        $this->logger->info(sprintf('[ownership-mail] Mail "%s" sent to %s', $subject, $to->toString()));
    }

    private function parseSubject(string $subject, string $name): string
    {
        return str_replace('{{ organisationName }}', $name, $subject);
    }
}
