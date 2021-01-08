<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

class UpdateEventStatusCommand extends AbstractUpdateOfferStatusCommand
{
    protected function configure(): void
    {
        $this
            ->setName('event:status:update')
            ->setDescription('Batch update status of events through SAPI 3 query.');
    }
}
