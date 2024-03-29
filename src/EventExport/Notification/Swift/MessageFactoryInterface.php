<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Swift;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

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
