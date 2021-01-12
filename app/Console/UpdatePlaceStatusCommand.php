<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Offer\OfferType;

class UpdatePlaceStatusCommand extends AbstractUpdateOfferStatusCommand
{
    protected function configure(): void
    {
        $this->offerType = OfferType::PLACE();

        $this
            ->setName('place:status:update')
            ->setDescription('Batch update status of places through SAPI 3 query.');
    }
}
