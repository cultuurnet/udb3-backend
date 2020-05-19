<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;

class CdbXMLEventFactory
{
    const AN_EVENT_ID = 'someId';
    /**
     * @var string
     */
    private $samplesPath;

    /**
     * @param string|null $samplesPath
     */
    public function __construct($samplesPath = null)
    {
        if (null === $samplesPath) {
            $samplesPath = __DIR__;
        }
        $this->samplesPath = $samplesPath;
    }

    /**
     * @param string $fileName
     * @return EventImportedFromUDB2
     */
    public function eventImportedFromUDB2($fileName)
    {
        return $this->eventFromFile($fileName, EventImportedFromUDB2::class);
    }

    /**
     * @param string $fileName
     * @return EventUpdatedFromUDB2
     */
    public function eventUpdatedFromUDB2($fileName)
    {
        return $this->eventFromFile($fileName, EventUpdatedFromUDB2::class);
    }

    /**
     * @param string $fileName
     * @param string $eventClass
     * @return mixed
     */
    private function eventFromFile($fileName, $eventClass)
    {
        $cdbXml = file_get_contents($this->samplesPath . '/' . $fileName);

        $event = new $eventClass(
            self::AN_EVENT_ID,
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        return $event;
    }
}
