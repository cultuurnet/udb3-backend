<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Calendar;

use CultureFeed_Cdb_Data_Calendar as Calendar;
use Doctrine\Common\Cache\Cache;

class CacheCalendarRepository implements CalendarRepositoryInterface
{
    protected Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $id): ?Calendar
    {
        $value = $this->cache->fetch($id);
        if ($value === false) {
            return null;
        }
        return unserialize($value);
    }

    public function save(string $id, Calendar $calendar): void
    {
        $calendar = serialize($calendar);
        $this->cache->save($id, $calendar, 0);
    }
}
