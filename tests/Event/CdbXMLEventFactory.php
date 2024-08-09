<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\SampleFiles;

class CdbXMLEventFactory
{
    public const AN_EVENT_ID = 'someId';

    private ?string $samplesPath;

    public function __construct(string $samplesPath = null)
    {
        if (null === $samplesPath) {
            $samplesPath = __DIR__;
        }
        $this->samplesPath = $samplesPath;
    }

    public function eventImportedFromUDB2(string $fileName): EventImportedFromUDB2
    {
        return $this->eventFromFile($fileName, EventImportedFromUDB2::class);
    }

    public function eventUpdatedFromUDB2(string $fileName): EventUpdatedFromUDB2
    {
        return $this->eventFromFile($fileName, EventUpdatedFromUDB2::class);
    }

    /**
     * @return EventImportedFromUDB2|EventUpdatedFromUDB2
     */
    private function eventFromFile(string $fileName, string $eventClass)
    {
        $cdbXml = SampleFiles::read($this->samplesPath . '/' . $fileName);

        $event = new $eventClass(
            self::AN_EVENT_ID,
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        return $event;
    }
}
