<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

class UpdatePlaceStatusCommand extends AbstractUpdateOfferStatusCommand
{
    protected function configure(): void
    {
        $this
            ->setName('place:status:update')
            ->setDescription('Batch update status of places through SAPI 3 query.');
    }
}
