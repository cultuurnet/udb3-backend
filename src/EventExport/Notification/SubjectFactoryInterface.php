<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;

interface SubjectFactoryInterface
{
    public function getSubjectFor(EventExportResult $eventExportResult);
}
