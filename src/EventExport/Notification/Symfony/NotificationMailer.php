<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Symfony;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\EventExport\Notification\NotificationMailerInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Symfony\Component\Mailer\MailerInterface;

class NotificationMailer implements NotificationMailerInterface
{
    private MailerInterface $mailer;

    private MessageFactoryInterface $messageFactory;

    public function __construct(
        MailerInterface $mailer,
        MessageFactoryInterface $mailFactory
    ) {
        $this->mailer = $mailer;
        $this->messageFactory = $mailFactory;
    }

    public function sendNotificationMail(
        EmailAddress $address,
        EventExportResult $eventExportResult
    ): void {
        $message = $this->messageFactory->createMessageFor($address, $eventExportResult);

        $this->mailer->send($message);
    }
}
