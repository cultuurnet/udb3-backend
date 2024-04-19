<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Parser;

use CultuurNet\UDB3\Kinepolis\ParsedPriceForATheater;

interface PriceParser
{
    public function parseTheaterPrices(array $theaterPrices): ParsedPriceForATheater;
}
