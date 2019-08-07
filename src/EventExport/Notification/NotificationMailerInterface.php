<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;
use ValueObjects\Web\EmailAddress;

interface NotificationMailerInterface
{
    public function sendNotificationMail(EmailAddress $address, EventExportResult $eventExportResult);
}
