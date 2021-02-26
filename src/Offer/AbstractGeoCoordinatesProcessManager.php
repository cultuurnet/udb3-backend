<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactoryInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractGeoCoordinatesProcessManager implements EventListener
{
    /**
     * @var CommandBus
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

    public function __construct(
        CommandBus $commandBus,
        CultureFeedAddressFactoryInterface $addressFactory,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->addressFactory = $addressFactory;
        $this->logger = $logger;
    }

    abstract protected function getEventHandlers();


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
