<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Address\CultureFeedAddressFactoryInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractGeoCoordinatesProcessManager implements EventListenerInterface
{
    /**
     * @var CommandBusInterface
     */
    protected $commandBus;

    /**
     * @var CultureFeedAddressFactoryInterface
     */
    protected $addressFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param CommandBusInterface $commandBus
     * @param CultureFeedAddressFactoryInterface $addressFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CommandBusInterface $commandBus,
        CultureFeedAddressFactoryInterface $addressFactory,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->addressFactory = $addressFactory;
        $this->logger = $logger;
    }

    abstract protected function getEventHandlers();

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
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
