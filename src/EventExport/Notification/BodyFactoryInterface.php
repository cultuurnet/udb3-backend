<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;

/**
 * Interface BodyFactoryInterface
 *
 * Implementations of BodyFactoryInterface are responsible for generating
 * the message body of a notification e-mail.
 */
interface BodyFactoryInterface
{

    public function getBodyFor(EventExportResult $eventExportResult);
}
