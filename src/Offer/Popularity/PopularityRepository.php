<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

interface PopularityRepository
{
    public function get(string $offerId): Popularity;
}
