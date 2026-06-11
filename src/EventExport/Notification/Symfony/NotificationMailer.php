<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Symfony;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\EventExport\Notification\BodyFactoryInterface;
use CultuurNet\UDB3\EventExport\Notification\NotificationMailerInterface;
use CultuurNet\UDB3\EventExport\Notification\SubjectFactoryInterface;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

class NotificationMailer implements NotificationMailerInterface
{
    private Mailer $mailer;

    private BodyFactoryInterface $plainTextBodyFactory;

    private BodyFactoryInterface $htmlBodyFactory;

    private SubjectFactoryInterface $subjectFactory;

    public function __construct(
        Mailer $mailer,
        BodyFactoryInterface $plainTextBodyFactory,
        BodyFactoryInterface $htmlBodyFactory,
        SubjectFactoryInterface $subjectFactory
    ) {
        $this->mailer = $mailer;
        $this->plainTextBodyFactory = $plainTextBodyFactory;
        $this->htmlBodyFactory = $htmlBodyFactory;
        $this->subjectFactory = $subjectFactory;
    }

    public function sendNotificationMail(
        EmailAddress $address,
        EventExportResult $eventExportResult
    ): void {
        $this->mailer->send(
            $address,
            $this->subjectFactory->getSubjectFor($eventExportResult),
            $this->htmlBodyFactory->getBodyFor($eventExportResult),
            $this->plainTextBodyFactory->getBodyFor($eventExportResult)
        );
    }
}
