<?php


namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion;

use CultureFeed_Uitpas_Event_CultureEvent;
use CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions;

interface PromotionQueryFactoryInterface
{
    /**
     * Creates a list of options that can be used as a query to retrieve promotions
     *
     * @param CultureFeed_Uitpas_Event_CultureEvent $event
     * @return CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions
     */
    public function createForEvent(CultureFeed_Uitpas_Event_CultureEvent $event);
}
