<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\Event\EventFacilityResolver;
use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\LegacyBridgeCategoryResolver;

class EventLegacyBridgeCategoryResolver extends LegacyBridgeCategoryResolver
{
    public function __construct()
    {
        parent::__construct(new EventTypeResolver(), new EventThemeResolver(), new EventFacilityResolver());
    }
}
