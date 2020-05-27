<?php

namespace CultuurNet\UDB3\Event\ReadModel\Calendar;

use \CultureFeed_Cdb_Data_Calendar as Calendar;

interface CalendarRepositoryInterface
{
    /**
     * @param string $id
     *   ID of the calendar to return.
     * @return Calendar|NULL
     *   The calendar data object if found, NULL otherwise.
     */
    public function get($id);

    /**
     * @param string $id
     *   ID of the calendar to store. Preferably use the ID of the event the calendar belongs to.
     * @param Calendar $calendar
     *   Calendar object to store.
     */
    public function save($id, Calendar $calendar);
}
