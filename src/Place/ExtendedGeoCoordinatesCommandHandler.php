<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use Psr\Log\LoggerInterface;

final class ExtendedGeoCoordinatesCommandHandler extends Udb3CommandHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handleUpdateGeoCoordinatesFromAddress(UpdateGeoCoordinatesFromAddress $updateGeoCoordinates): void
    {
        $this->logger->info(sprintf(
            'Handling %s command for place with id: %s',
            UpdateGeoCoordinatesFromAddress::class,
            $updateGeoCoordinates->getItemId()
        ));
    }
}
