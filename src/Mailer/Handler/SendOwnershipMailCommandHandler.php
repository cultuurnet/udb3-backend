<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Mailer\Handler\Helper\OwnershipMailParamExtractor;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\RecipientStrategy;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\User\UserIdentityDetails;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class SendOwnershipMailCommandHandler implements CommandHandler
{
    private const SUBJECT_OWNERSHIP_REQUESTED = 'Beheers aanvraag voor organisatie {{ organisationName }}';
    private const TEMPLATE_OWNERSHIP_REQUESTED = 'ownershipRequested';

    private Mailer $mailer;
    private MailsSentRepository $mailsSentRepository;
    private TwigEnvironment $twig;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private OwnershipMailParamExtractor $paramExtractor;
    private RecipientStrategy $sendToCreatorOfOrganisation;
    private LoggerInterface $logger;

    public function __construct(
        Mailer $mailer,
        MailsSentRepository $mailsSentRepository,
        TwigEnvironment $twig,
        OwnershipSearchRepository $ownershipSearchRepository,
        OwnershipMailParamExtractor $paramExtractor,
        RecipientStrategy $sendToCreatorOfOrganisation,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->mailsSentRepository = $mailsSentRepository;
        $this->twig = $twig;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->paramExtractor = $paramExtractor;
        $this->sendToCreatorOfOrganisation = $sendToCreatorOfOrganisation;
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
                    $this->sendToCreatorOfOrganisation,
                );
                break;
        }
    }

    private function processCommand(SendOwnershipRequestedMail $command, string $rawSubject, string $template, RecipientStrategy $recipientStrategy): void
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
            $recipients = $recipientStrategy->getRecipients($ownershipItem);
        } catch (DocumentDoesNotExist $e) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load organizer: %s', $e->getMessage()));
            return;
        }

        foreach ($recipients as $userIdentityDetails) {
            $this->sendMail(
                $userIdentityDetails,
                $ownershipItem,
                $rawSubject,
                $template,
                $command
            );
        }
    }

    public function sendMail(
        UserIdentityDetails $userIdentityDetails,
        OwnershipItem $ownershipItem,
        string $rawSubject,
        string $template,
        SendOwnershipRequestedMail $command
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

        $this->mailsSentRepository->addMailSent(new Uuid($ownershipItem->getId()), $to, get_class($command), new DateTimeImmutable());

        $this->logger->info(sprintf('[ownership-mail] Mail "%s" sent to %s', $subject, $to->toString()));
    }

    private function parseSubject(string $subject, string $name): string
    {
        return str_replace('{{ organisationName }}', $name, $subject);
    }
}
