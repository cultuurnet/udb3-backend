<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultureFeed_Cdb_Item_Base;
use stdClass;

final class CdbXMLToJsonLDLabelImporter
{
    public function importLabels(CultureFeed_Cdb_Item_Base $item, stdClass $jsonLD): void
    {
        $keywords = array_values($item->getKeywords(true));
        $labels = LabelsFactory::createLabelsFromKeywords(...$keywords);

        $visibleLabels = $labels->getVisibleLabels()->toArrayOfStringNames();
        $hiddenLabels = $labels->getHiddenLabels()->toArrayOfStringNames();

        empty($visibleLabels) ?: $jsonLD->labels = $visibleLabels;
        empty($hiddenLabels) ?: $jsonLD->hiddenLabels = $hiddenLabels;
    }
}
