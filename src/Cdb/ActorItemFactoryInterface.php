<?php

namespace CultuurNet\UDB3\Cdb;

interface ActorItemFactoryInterface
{
    /**
     * @param string $cdbXml
     * @throws \CultureFeed_Cdb_ParseException
     * @return \CultureFeed_Cdb_Item_Actor
     */
    public function createFromCdbXml($cdbXml);
}
