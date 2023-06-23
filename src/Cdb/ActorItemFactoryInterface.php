<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

interface ActorItemFactoryInterface
{
    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    public function createFromCdbXml(string $cdbXml): \CultureFeed_Cdb_Item_Actor;
}
