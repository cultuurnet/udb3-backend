<?php

namespace CultuurNet\UDB3\UDB2\Media;

use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Media\ImageCollection;

interface ImageCollectionFactoryInterface
{
    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @return ImageCollection
     */
    public function fromUdb2Item(CultureFeed_Cdb_Item_Base $item);
}
