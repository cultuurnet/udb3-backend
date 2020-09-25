<?php

namespace CultuurNet\UDB3;

use CultureFeed_Cdb_Item_Base;
use stdClass;

class LabelImporter
{
    public function importLabels(CultureFeed_Cdb_Item_Base $item, stdClass $jsonLD)
    {
        $labelCollection = LabelCollection::fromKeywords(
            $item->getKeywords(true)
        );

        $visibleLabels = $labelCollection->filter(
            function (Label $label) {
                return $label->isVisible();
            }
        )->toStrings();

        $hiddenLabels = $labelCollection->filter(
            function (Label $label) {
                return !$label->isVisible();
            }
        )->toStrings();

        empty($visibleLabels) ?: $jsonLD->labels = $visibleLabels;
        empty($hiddenLabels) ?: $jsonLD->hiddenLabels = $hiddenLabels;
    }
}
