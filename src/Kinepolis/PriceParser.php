<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

interface PriceParser
{
    public function parseTheaterPrices(array $theaterPrices): ParsedPrice;
}
