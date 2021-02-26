<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\CdbId;

interface EventCdbIdExtractorInterface
{
    /**
     *
     * @return string|null
     *   CdbId of the related place, or null if no CdbId could be found.
     */
    public function getRelatedPlaceCdbId(\CultureFeed_Cdb_Item_Event $cdbEvent);

    /**
     *
     * @return string|null
     *   CdbId of the related place, or null if no CdbId could be found.
     */
    public function getRelatedOrganizerCdbId(\CultureFeed_Cdb_Item_Event $cdbEvent);
}
