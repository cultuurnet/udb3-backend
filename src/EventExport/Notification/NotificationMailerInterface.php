<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

interface NotificationMailerInterface
{
    public function sendNotificationMail(EmailAddress $address, EventExportResult $eventExportResult): void;
}
