<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DomainEventStream;
use Closure;
use CultuurNet\UDB3\EventBus\EventBusMiddleware;

final class CallbackOnFirstPublicationMiddleware implements EventBusMiddleware
{
    private int $publicationCount = 0;
    private Closure $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function beforePublish(DomainEventStream $domainEventStream): DomainEventStream
    {
        $this->publicationCount++;
        if ($this->publicationCount === 1) {
            call_user_func($this->callback);
        }
        return $domainEventStream;
    }
}
