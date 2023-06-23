<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Calendar;

use CultureFeed_Cdb_Data_Calendar as Calendar;

interface CalendarRepositoryInterface
{
    public function get(string $id): ?Calendar;

    public function save(string $id, Calendar $calendar): void;
}
