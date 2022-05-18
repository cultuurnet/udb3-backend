<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\PriceInfo\PriceInfo;

final class UpdatePriceInfo extends AbstractCommand
{
    protected PriceInfo $priceInfo;

    public function __construct(string $offerId, PriceInfo $priceInfo)
    {
        parent::__construct($offerId);
        $this->priceInfo = $priceInfo;
    }

    public function getPriceInfo(): PriceInfo
    {
        return $this->priceInfo;
    }
}
