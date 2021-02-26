<?php

namespace CultuurNet\UDB3\EventExport\Notification\Swift;

use CultuurNet\UDB3\EventExport\EventExportResult;
use ValueObjects\Web\EmailAddress;

interface MessageFactoryInterface
{
    /**
     * @return \Swift_Message
     */
    public function createMessageFor(
        EmailAddress $address,
        EventExportResult $eventExportResult
    );
}
