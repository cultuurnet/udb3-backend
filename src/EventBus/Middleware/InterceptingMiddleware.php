<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Closure;
use CultuurNet\UDB3\EventBus\EventBusMiddleware;

/**
 * Intercepts domain messages that match specific criteria, so they do not get published.
 * Intercepted domain messages can later be retrieved.
 */
final class InterceptingMiddleware implements EventBusMiddleware
{
    /**
     * @var DomainMessage[]
     */
    private static array $intercepted = [];
    private static ?Closure $interceptCallback = null;

    /**
     * Static so that if the event bus or this middleware is accidentally instantiated twice, it is in sync across all
     * instances.
     *
     * @param Closure $filterCallback
     *   Gets called for every DomainMessage that would be published on the EventBus.
     *   The first and only argument is the DomainMessage.
     *   Should return true if the DomainMessage should be intercepted, or false if it may be published.
     */
    public static function startIntercepting(Closure $filterCallback): void
    {
        self::$interceptCallback = $filterCallback;
    }

    /**
     * Static so that if the event bus or this middleware is accidentally instantiated twice, it is in sync across all
     * instances.
     */
    public static function stopIntercepting(): void
    {
        self::$interceptCallback = null;
    }

    /**
     * Static so that if the event bus or this middleware is accidentally instantiated twice, it is in sync across all
     * instances.
     */
    public static function getInterceptedMessagesWithUniquePayload(): DomainEventStream
    {
        $unique = [];
        $uniquePayloads = [];
        foreach (self::$intercepted as $intercepted) {
            if (!in_array($intercepted->getPayload(), $uniquePayloads, false)) {
                $unique[] = $intercepted;
                $uniquePayloads[] = $intercepted->getPayload();
            }
        }
        return new DomainEventStream($unique);
    }

    public function beforePublish(DomainEventStream $domainEventStream): DomainEventStream
    {
        if (self::$interceptCallback === null) {
            return $domainEventStream;
        }

        $publishMessages = [];
        foreach ($domainEventStream as $domainMessage) {
            $shouldIntercept = call_user_func(self::$interceptCallback, $domainMessage);
            if ($shouldIntercept === true) {
                self::$intercepted[] = $domainMessage;
                continue;
            }
            $publishMessages[] = $domainMessage;
        }

        return new DomainEventStream($publishMessages);
    }
}
