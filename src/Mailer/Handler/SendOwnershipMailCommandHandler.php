<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Mailer\Handler\Helper\OwnershipMailParamExtractor;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
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
            $this->logger->info(sprintf('[ownership-mail] Mail %s about %s was already sent', $uuid->toString(), get_class($command)));
            return;
        }

        try {
            $ownershipItem = $this->ownershipSearchRepository->getById($uuid->toString());
        } catch (OwnershipItemNotFound $e) {
            $this->logger->warning('[ownership-mail] ' . $e->getMessage());
            return;
        }

        //@todo loop over ALL owners of organisation
        $ownerId = $ownershipItem->getOwnerId();
        $ownerDetails = $this->identityResolver->getUserById($ownerId);

        if ($ownerDetails === null) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load owner details for %s', $ownerId));
            return;
        }

        try {
            $params = $this->paramExtractor->fetchParams($ownershipItem, $ownerDetails);
        } catch (DocumentDoesNotExist $e) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load organizer: %s', $e->getMessage()));
            return;
        }

        $subject = $this->parseSubject($rawSubject, $params['organisationName']);
        $to = new EmailAddress($ownerDetails->getEmailAddress());

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

        $this->mailsSentRepository->addMailSent($uuid, $to, get_class($command), new DateTimeImmutable());

        $this->logger->info(sprintf('[ownership-mail] Mail "%s" sent to %s', $subject, $to->toString()));
    }

    private function parseSubject(string $subject, string $name): string
    {
        return str_replace('{{ organisationName }}', $name, $subject);
    }
}
