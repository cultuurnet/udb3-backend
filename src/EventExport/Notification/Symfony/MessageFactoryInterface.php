<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Symfony;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Symfony\Component\Mime\Email;

interface MessageFactoryInterface
{
    public function createMessageFor(
        EmailAddress $address,
        EventExportResult $eventExportResult
    ): Email;
}
