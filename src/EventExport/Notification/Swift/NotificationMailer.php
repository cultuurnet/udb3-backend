<?php

namespace CultuurNet\UDB3\EventExport\Notification\Swift;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\EventExport\Notification\NotificationMailerInterface;
use ValueObjects\Web\EmailAddress;

class NotificationMailer implements NotificationMailerInterface
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @param \Swift_Mailer           $mailer
     * @param MessageFactoryInterface $mailFactory
     */
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
    ) {
        $message = $this->messageFactory->createMessageFor($address, $eventExportResult);

        $sent = $this->mailer->send($message);

        print 'sent '.$sent.' e-mails'.PHP_EOL;
    }
}
