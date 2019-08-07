<?php

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfo;

class UitpasInfoFormatter
{
    /**
     * @var PriceFormatter;
     */
    protected $priceFormatter;

    /**
     * UitpasInfoFormatter constructor.
     * @param PriceFormatter $priceFormatter
     */
    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * @param EventInfo $uitpasInfo
     * @return array
     */
    public function format(EventInfo $uitpasInfo)
    {
        // Format prices.
        $prices = $uitpasInfo->getPrices();
        foreach ($prices as &$price) {
            $price['price'] = $this->priceFormatter->format($price['price']);
        }

        // Format advantage labels. Start from a list of all known
        // advantage labels, and filter out the ones that don't apply.
        // Otherwise the order could get mixed up.
        $advantages = $uitpasInfo->getAdvantages();
        $advantageLabels = [
            EventAdvantage::POINT_COLLECTING => 'Spaar punten',
            EventAdvantage::KANSENTARIEF => 'Korting voor kansentarief',
        ];
        foreach ($advantageLabels as $advantage => $advantageLabel) {
            if (!in_array($advantage, $advantages)) {
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
