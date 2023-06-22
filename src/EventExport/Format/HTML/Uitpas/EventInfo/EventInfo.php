<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo;

use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;

class EventInfo
{
    protected array $prices;

    /**
     * @var EventAdvantage[]
     */
    protected array $advantages;

    /**
     * @var string[]
     */
    protected array $promotions;

    /**
     * @param EventAdvantage[] $advantages
     * @param string[] $promotions
     */
    public function __construct(array $prices, array $advantages, array $promotions)
    {
        $this->prices = $prices;
        $this->advantages = $advantages;
        $this->promotions = $promotions;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }

    /**
     * @return EventAdvantage[]
     */
    public function getAdvantages(): array
    {
        return $this->advantages;
    }

    /**
     * @return string[]
     */
    public function getPromotions(): array
    {
        return $this->promotions;
    }
}
