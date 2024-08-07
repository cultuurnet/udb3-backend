<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\SimpleEventBus;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\EventBus\Middleware\InterceptingMiddleware;
use CultuurNet\UDB3\EventBus\MiddlewareEventBus;
use CultuurNet\UDB3\EventBus\TraceableEventBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Events\AbstractEventWithIri;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ProjectedToJSONLDInterceptingMiddlewareTest extends TestCase
{
    private TraceableEventBus $traceableEventBus;
    private EventBus $middlewareEventBus;
    private ProjectedToJSONLDInterceptingMiddleware $projectedToJSONLDInterceptingMiddleware;

    protected function setUp(): void
    {
        $this->traceableEventBus = new TraceableEventBus(new SimpleEventBus());
        $this->traceableEventBus->trace();
        $this->middlewareEventBus = new MiddlewareEventBus($this->traceableEventBus);
        $this->middlewareEventBus->registerMiddleware(new InterceptingMiddleware());
        $this->projectedToJSONLDInterceptingMiddleware = new ProjectedToJSONLDInterceptingMiddleware(
            $this->middlewareEventBus
        );
    }

    /**
     * @test
     */
    public function it_intercepts_projected_to_jsonld_messages_while_the_request_is_being_handled_and_republishes_the_unique_ones_afterwards(): void
    {
        // Example request handler that publishes a lot of (duplicate) ProjectedToJSONLD messages on the event bus
        $requestHandler = new class ($this->middlewareEventBus) implements RequestHandlerInterface {
            private EventBus $eventBus;
            public function __construct(EventBus $eventBus)
            {
                $this->eventBus = $eventBus;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $messages = [
                    new EventProjectedToJSONLD(
                        '5e98e17b-51e2-4fdf-a3bb-9da222d5db14',
                        'https://io.uitdatabank.dev/events/5e98e17b-51e2-4fdf-a3bb-9da222d5db14'
                    ),
                    new EventProjectedToJSONLD(
                        '5e98e17b-51e2-4fdf-a3bb-9da222d5db14',
                        'https://io.uitdatabank.dev/events/5e98e17b-51e2-4fdf-a3bb-9da222d5db14'
                    ),
                    new EventProjectedToJSONLD(
                        '5e98e17b-51e2-4fdf-a3bb-9da222d5db14',
                        'https://io.uitdatabank.dev/events/5e98e17b-51e2-4fdf-a3bb-9da222d5db14'
                    ),
                    new EventProjectedToJSONLD(
                        '5e98e17b-51e2-4fdf-a3bb-9da222d5db14',
                        'https://io.uitdatabank.dev/events/5e98e17b-51e2-4fdf-a3bb-9da222d5db14'
                    ),
                    new PlaceProjectedToJSONLD(
                        'c118f5c5-45ce-4c07-846b-e700f437903c',
                        'https://io.uitdatabank.dev/places/c118f5c5-45ce-4c07-846b-e700f437903c'
                    ),
                    new PlaceProjectedToJSONLD(
                        'c118f5c5-45ce-4c07-846b-e700f437903c',
                        'https://io.uitdatabank.dev/places/c118f5c5-45ce-4c07-846b-e700f437903c'
                    ),
                    new PlaceProjectedToJSONLD(
                        'c118f5c5-45ce-4c07-846b-e700f437903c',
                        'https://io.uitdatabank.dev/places/c118f5c5-45ce-4c07-846b-e700f437903c'
                    ),
                    new OrganizerProjectedToJSONLD(
                        '364c99ea-9945-4f98-857b-10240be25f4e',
                        'https://io.uitdatabank.dev/organizers/364c99ea-9945-4f98-857b-10240be25f4e'
                    ),
                    new OrganizerProjectedToJSONLD(
                        '364c99ea-9945-4f98-857b-10240be25f4e',
                        'https://io.uitdatabank.dev/organizers/364c99ea-9945-4f98-857b-10240be25f4e'
                    ),
                ];

                /** @var AbstractEventWithIri|OrganizerProjectedToJSONLD $message */
                foreach ($messages as $message) {
                    $id = $message instanceof AbstractEventWithIri ? $message->getItemId() : $message->getId();
                    $this->eventBus->publish(
                        new DomainEventStream(
                            [DomainMessage::recordNow($id, 0, new Metadata(), $message)]
                        )
                    );
                }
                return new NoContentResponse();
            }
        };

        $expected = [
            new EventProjectedToJSONLD(
                '5e98e17b-51e2-4fdf-a3bb-9da222d5db14',
                'https://io.uitdatabank.dev/events/5e98e17b-51e2-4fdf-a3bb-9da222d5db14'
            ),
            new PlaceProjectedToJSONLD(
                'c118f5c5-45ce-4c07-846b-e700f437903c',
                'https://io.uitdatabank.dev/places/c118f5c5-45ce-4c07-846b-e700f437903c'
            ),
            new OrganizerProjectedToJSONLD(
                '364c99ea-9945-4f98-857b-10240be25f4e',
                'https://io.uitdatabank.dev/organizers/364c99ea-9945-4f98-857b-10240be25f4e'
            ),
        ];

        $this->projectedToJSONLDInterceptingMiddleware->process((new Psr7RequestBuilder())->build('GET'), $requestHandler);

        $actual = $this->traceableEventBus->getEvents();

        $this->assertEquals($expected, $actual);
    }
}
