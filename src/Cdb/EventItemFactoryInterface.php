<?php

namespace CultuurNet\UDB3\Cdb;

interface EventItemFactoryInterface
{
    /**
     * @param string $cdbXml
     * @throws \CultureFeed_Cdb_ParseException
     * @return \CultureFeed_Cdb_Item_Event
     */
    public function createFromCdbXml($cdbXml);
}
