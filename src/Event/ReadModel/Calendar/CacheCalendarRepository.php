<?php

namespace CultuurNet\UDB3\Event\ReadModel\Calendar;

use CultureFeed_Cdb_Data_Calendar as Calendar;
use Doctrine\Common\Cache\Cache;

class CacheCalendarRepository implements CalendarRepositoryInterface
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $value = $this->cache->fetch($id);
        if ($value === false) {
            return null;
        }
        return unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, Calendar $calendar)
    {
        $calendar = serialize($calendar);
        $this->cache->save($id, $calendar, 0);
    }
}
