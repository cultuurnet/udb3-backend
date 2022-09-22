<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\EventBus\Middleware\InterceptingMiddleware;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Limits the amount of "ProjectedToJSONLD" messages on the AMQP queues by intercepting them on the event bus
 * during the request handling, and only (re)publishing the unique ones after the request handler is done.
 * Note that before() and after() callbacks are only called in the context of HTTP requests, so not in CLI
 * commands where we don't want this behavior.
 * Some examples where this is useful:
 * - New event, place, organizer imports that contain one or more non-required fields which results in extra
 *   ProjectedToJSONLD messages
 * - Event, place, organizer updates via imports that edit multiple fields which results in multiple ProjectedToJSONLD
 *   messages
 * - Place creation that also does a geocoding which results in 2 PlaceProjectedToJSONLD messages
 * - Organizer address update that also does a geocoding which results in 2 OrganizerProjectedToJSONLD messages
 */
final class ProjectedToJSONLDInterceptingMiddleware implements MiddlewareInterface
{
    private EventBus $eventBus;

    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        InterceptingMiddleware::startIntercepting(
            function (DomainMessage $message): bool {
                $payload = $message->getPayload();
                return $payload instanceof EventProjectedToJSONLD ||
                    $payload instanceof PlaceProjectedToJSONLD ||
                    $payload instanceof OrganizerProjectedToJSONLD;
            }
        );

        $response = $handler->handle($request);

        InterceptingMiddleware::stopIntercepting();
        $interceptedWithUniquePayload = InterceptingMiddleware::getInterceptedMessagesWithUniquePayload();

        // Important! Only publish the intercepted messages if there are actually any. Otherwise the EventBus
        // middlewares will be triggered for requests that do not require it, which in turn will trigger the
        // command bus to be instantiated by the CallbackOnFirstPublicationMiddleware. And the command bus requires the
        // current user id to work, which is not available on all requests (for example OPTIONS requests, or public GET
        // requests).
        if ($interceptedWithUniquePayload->getIterator()->count() > 0) {
            $this->eventBus->publish($interceptedWithUniquePayload);
        }

        return $response;
    }
}
