<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\CommandHandling\CommandBusDecoratorBase;
use CultuurNet\UDB3\CommandHandling\ContextAwareInterface;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;

class ContextDecoratedCommandBus extends CommandBusDecoratorBase
{
    private ?string $userId;

    private ?JsonWebToken $jwt;

    private ?ApiKey $apiKey;

    private ?string $apiName;

    private ?Consumer $consumer;

    public function __construct(
        CommandBus $decoratee,
        ?string $userId = null,
        ?JsonWebToken $jwt = null,
        ?ApiKey $apiKey = null,
        ?string $apiName = null,
        ?Consumer $consumer = null
    ) {
        parent::__construct($decoratee);
        $this->userId = $userId;
        $this->jwt = $jwt;
        $this->apiKey = $apiKey;
        $this->apiName = $apiName;
        $this->consumer = $consumer;
    }

    public function dispatch($command): void
    {
        if ($this->decoratee instanceof ContextAwareInterface) {
            $context = ContextFactory::createContext(
                $this->userId,
                $this->jwt,
                $this->apiKey,
                $this->apiName,
                $this->consumer
            );
            $this->decoratee->setContext($context);
        }
        $this->decoratee->dispatch($command);
    }
}
