<?php
/**
 * @file
 */
namespace CultuurNet\UDB3\EventExport\Notification\Swift;

use CultuurNet\UDB3\EventExport\EventExportResult;
use ValueObjects\Web\EmailAddress;

interface MessageFactoryInterface
{
    /**
     * @param EmailAddress $address
     * @param EventExportResult $eventExportResult
     * @return \Swift_Message
     */
    public function createMessageFor(
        EmailAddress $address,
        EventExportResult $eventExportResult
    );
}
