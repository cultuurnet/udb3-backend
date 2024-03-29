<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Swift;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\EventExport\Notification\NotificationMailerInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

class NotificationMailer implements NotificationMailerInterface
{
    private \Swift_Mailer $mailer;

    private MessageFactoryInterface $messageFactory;

    public function __construct(
        \Swift_Mailer $mailer,
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

        $sent = $this->mailer->send($message);

        print 'sent ' . $sent . ' e-mails' . PHP_EOL;
    }
}
