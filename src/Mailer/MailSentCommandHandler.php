<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\CommandHandling\AsyncCommandTrait;
use CultuurNet\UDB3\Mailer\Event\SentMail;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class MailSentCommandHandler implements CommandHandler, AsyncCommand
{
    use AsyncCommandTrait;

    private Mailer $mailer;
    private MailsSentRepository $mailsSentRepository;
    private LoggerInterface $logger;

    public function __construct(Mailer $mailer, MailsSentRepository $mailsSentRepository, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->mailsSentRepository = $mailsSentRepository;
        $this->logger = $logger;
    }

    public function handle($command): void
    {
        if (!$command instanceof SentMail) {
            return;
        }

        if ($this->mailsSentRepository->isMailSent($command->getUuid(), $command->getSubject())) {
            return;
        }

        $success = $this->mailer->send(
            $command->getTo(),
            $command->getSubject(),
            $command->getHtml(),
            $command->getText()
        );

        if (! $success) {
            return;
        }

        $this->mailsSentRepository->addMailSent($command->getUuid(), $command->getTo(), $command->getSubject(), new DateTimeImmutable());

        $this->logger->info(sprintf('Mail "%s" sent to %s', $command->getSubject(), $command->getTo()->toString()));
    }
}
