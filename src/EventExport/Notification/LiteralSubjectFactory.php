<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;

class LiteralSubjectFactory implements SubjectFactoryInterface
{
    private string $subject;

    public function __construct(string $subject)
    {
        $this->subject = $subject;
    }

    public function getSubjectFor(EventExportResult $eventExportResult): string
    {
        return $this->subject;
    }
}
