<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfo;

class UitpasInfoFormatter
{
    protected PriceFormatter $priceFormatter;

    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    public function format(EventInfo $uitpasInfo): array
    {
        // Format prices.
        $prices = $uitpasInfo->getPrices();
        foreach ($prices as &$price) {
            $price['price'] = $this->priceFormatter->format((float) $price['price']);
        }

        // Format advantage labels. Start from a list of all known
        // advantage labels, and filter out the ones that don't apply.
        // Otherwise the order could get mixed up.
        $advantages = $uitpasInfo->getAdvantages();
        $advantageLabels = [
            EventAdvantage::pointCollecting()->toString() => 'Spaar punten',
            EventAdvantage::kansenTarief()->toString() => 'Korting voor kansentarief',
        ];
        foreach ($advantageLabels as $advantage => $advantageLabel) {
            if (!in_array(new EventAdvantage($advantage), $advantages)) {
                unset($advantageLabels[$advantage]);
            }
        }
        $advantages = array_values($advantageLabels);

        // Add all uitpas info to the event.
        return [
            'prices' => $prices,
            'advantages' => $advantages,
            'promotions' => $uitpasInfo->getPromotions(),
        ];
    }
}
