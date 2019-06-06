<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo;

use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;

class EventInfo
{
    /**
     * @var array
     */
    protected $prices;

    /**
     * @var EventAdvantage[]
     */
    protected $advantages;

    /**
     * @var string[]
     */
    protected $promotions;

    /**
     * @param array $prices
     * @param EventAdvantage[] $advantages
     * @param string[] $promotions
     */
    public function __construct($prices, $advantages, $promotions)
    {
        $this->prices = $prices;
        $this->advantages = $advantages;
        $this->promotions = $promotions;
    }

    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @return EventAdvantage[]
     */
    public function getAdvantages()
    {
        return $this->advantages;
    }

    /**
     * @return string[]
     */
    public function getPromotions()
    {
        return $this->promotions;
    }
}
