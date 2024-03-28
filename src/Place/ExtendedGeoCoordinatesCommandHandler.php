<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Security\AuthorizableCommand;
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
        $this->saveExtendedCoordinates($updateGeoCoordinates);
    }

    public function handleUpdateTitle(UpdateTitle $updateTitle): void
    {
        $this->saveExtendedCoordinates($updateTitle);
    }

    private function saveExtendedCoordinates(AuthorizableCommand $command): void
    {
        $this->logger->info(sprintf('Handling %s command for place with id: %s', get_class($command), $command->getItemId()));
    }
}
