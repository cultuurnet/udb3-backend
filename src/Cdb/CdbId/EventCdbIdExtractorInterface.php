<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\CdbId;

interface EventCdbIdExtractorInterface
{
    public function getRelatedPlaceCdbId(\CultureFeed_Cdb_Item_Event $cdbEvent): ?string;

    public function getRelatedOrganizerCdbId(\CultureFeed_Cdb_Item_Event $cdbEvent): ?string;
}
