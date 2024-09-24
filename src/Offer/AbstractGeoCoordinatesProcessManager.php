<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactoryInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractGeoCoordinatesProcessManager implements EventListener
{
    protected CommandBus $commandBus;

    protected CultureFeedAddressFactoryInterface $addressFactory;

    protected LoggerInterface $logger;

    public function __construct(
        CommandBus $commandBus,
        CultureFeedAddressFactoryInterface $addressFactory,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->addressFactory = $addressFactory;
        $this->logger = $logger;
    }

    abstract protected function getEventHandlers(): array;

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $className = get_class($payload);
        $eventHandlers = $this->getEventHandlers();

        if (isset($eventHandlers[$className])) {
            $eventHandler = $eventHandlers[$className];
            call_user_func([$this, $eventHandler], $payload);
        }
    }
}
