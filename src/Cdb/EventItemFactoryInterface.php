<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

interface EventItemFactoryInterface
{
    /**
     * @param string $cdbXml
     * @throws \CultureFeed_Cdb_ParseException
     */
    public function createFromCdbXml($cdbXml): \CultureFeed_Cdb_Item_Event;
}
