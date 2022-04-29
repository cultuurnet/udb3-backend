<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelCollection;
use stdClass;

final class CdbXMLToJsonLDLabelImporter
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
