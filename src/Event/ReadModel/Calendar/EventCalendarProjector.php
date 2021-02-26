<?php

namespace CultuurNet\UDB3\Event\ReadModel\Calendar;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\EventCdbXMLInterface;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;

class EventCalendarProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var CalendarRepositoryInterface
     */
    protected $repository;


    public function __construct(CalendarRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }


    public function applyEventImportedFromUDB2(EventImportedFromUDB2 $eventImportedFromUDB2)
    {
        $this->saveEventCalendar($eventImportedFromUDB2);
    }


    public function applyEventUpdatedFromUDB2(EventUpdatedFromUDB2 $eventUpdatedFromUDB2)
    {
        $this->saveEventCalendar($eventUpdatedFromUDB2);
    }


    private function saveEventCalendar(EventCdbXMLInterface $eventEvent)
    {
        $eventId = $eventEvent->getEventId();

        $event = EventItemFactory::createEventFromCdbXml(
            $eventEvent->getCdbXmlNamespaceUri(),
            $eventEvent->getCdbXml()
        );

        $this->repository->save($eventId, $event->getCalendar());
    }
}
