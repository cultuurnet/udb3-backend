<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;

class LiteralSubjectFactory implements SubjectFactoryInterface
{
    /**
     * @var string
     */
    private $subject;

    /**
     * @param string $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    public function getSubjectFor(EventExportResult $eventExportResult)
    {
        return $this->subject;
    }
}
