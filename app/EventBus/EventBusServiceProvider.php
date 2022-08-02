<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\EventBus;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Broadway\AMQP\AMQPPublisher;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Event\RelocateEventToCanonicalPlace;
use CultuurNet\UDB3\EventBus\Middleware\CallbackOnFirstPublicationMiddleware;
use CultuurNet\UDB3\EventBus\Middleware\InterceptingMiddleware;
use CultuurNet\UDB3\EventBus\Middleware\ReplayFlaggingMiddleware;
use CultuurNet\UDB3\EventBus\MiddlewareEventBus;
use CultuurNet\UDB3\Label\ReadModels\JSON\LabelVisibilityOnRelatedDocumentsProjector;
use CultuurNet\UDB3\Offer\ProcessManagers\AutoApproveForUiTIDv1ApiKeysProcessManager;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataProjector;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Silex\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerPermissionServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class EventBusServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[EventBus::class] = $app::share(
            function ($app) {
                $eventBus = new MiddlewareEventBus();

                $callbackMiddleware = new CallbackOnFirstPublicationMiddleware(
                    function () use (&$eventBus, $app): void {
                        $subscribers = [
                            'event_relations_projector',
                            'place_relations_projector',
                            EventJSONLDServiceProvider::PROJECTOR,
                            \CultuurNet\UDB3\Event\ReadModel\History\HistoryProjector::class,
                            \CultuurNet\UDB3\Place\ReadModel\History\HistoryProjector::class,
                            PlaceJSONLDServiceProvider::PROJECTOR,
                            OrganizerJSONLDServiceProvider::PROJECTOR,
                            'event_calendar_projector',
                            'event_permission.projector',
                            'place_permission.projector',
                            OrganizerPermissionServiceProvider::PERMISSION_PROJECTOR,
                            AMQPPublisher::class,
                            'udb2_events_cdbxml_enricher',
                            'udb2_actor_events_cdbxml_enricher',
                            'udb2_events_to_udb3_event_applier',
                            'udb2_actor_events_to_udb3_place_applier',
                            'udb2_actor_events_to_udb3_organizer_applier',
                            'udb2_label_importer',
                            LabelServiceProvider::JSON_PROJECTOR,
                            LabelServiceProvider::RELATIONS_PROJECTOR,
                            LabelServiceProvider::LABEL_ROLES_PROJECTOR,
                            LabelVisibilityOnRelatedDocumentsProjector::class,
                            'role_detail_projector',
                            'role_labels_projector',
                            'label_roles_projector',
                            'role_search_v3_projector',
                            'role_users_projector',
                            'user_roles_projector',
                            UserPermissionsServiceProvider::USER_PERMISSIONS_PROJECTOR,
                            OfferMetadataProjector::class,
                            'place_geocoordinates_process_manager',
                            'event_geocoordinates_process_manager',
                            'organizer_geocoordinates_process_manager',
                            'uitpas_event_process_manager',
                            'curators_news_article_process_manager',
                            RelocateEventToCanonicalPlace::class,
                            AutoApproveForUiTIDv1ApiKeysProcessManager::class,
                        ];

                        $initialSubscribersCount = count($subscribers);
                        $subscribers = array_unique($subscribers);
                        if ($initialSubscribersCount != count($subscribers)) {
                            throw new \Exception('Some projectors are subscribed more then once!');
                        }

                        // Allow to override event bus subscribers through configuration.
                        // The event replay command line utility uses this.
                        if (isset($app['config']['event_bus']['subscribers'])) {
                            $subscribers = $app['config']['event_bus']['subscribers'];
                        }

                        $disableRelatedOfferSubscribers = $app['config']['event_bus']['disable_related_offer_subscribers'] ?? false;
                        if ($disableRelatedOfferSubscribers === true) {
                            $subscribersToDisable = [];
                            $subscribers = array_diff($subscribers, $subscribersToDisable);
                        }

                        foreach ($subscribers as $subscriberServiceId) {
                            $eventBus->subscribe($app[$subscriberServiceId]);
                        }
                    }
                );

                $eventBus->registerMiddleware($callbackMiddleware);
                $eventBus->registerMiddleware(new ReplayFlaggingMiddleware());
                $eventBus->registerMiddleware(new InterceptingMiddleware());
                return $eventBus;
            }
        );
    }

    public function boot(Application $app): void
    {
        // Limit the amount of "ProjectedToJSONLD" messages on the AMQP queues by intercepting them on the event bus
        // during the request handling, and only (re)publishing the unique ones after the request handler is done.
        // Note that before() and after() callbacks are only called in the context of HTTP requests, so not in CLI
        // commands where we don't want this behavior.
        // Some examples where this is useful:
        // - New event, place, organizer imports that contain one or more non-required fields which results in extra ProjectedToJSONLD messages
        // - Event, place, organizer updates via imports that edit multiple fields which results in multiple ProjectedToJSONLD messages
        // - Place creation that also does a geocoding which results in 2 PlaceProjectedToJSONLD messages
        // - Organizer address update that also does a geocoding which results in 2 OrganizerProjectedToJSONLD messages
        $app->before(
            function () {
                InterceptingMiddleware::startIntercepting(
                    function (DomainMessage $message): bool {
                        $payload = $message->getPayload();
                        return $payload instanceof EventProjectedToJSONLD ||
                            $payload instanceof PlaceProjectedToJSONLD ||
                            $payload instanceof OrganizerProjectedToJSONLD;
                    }
                );
            }
        );
        $app->after(
            function () use ($app) {
                InterceptingMiddleware::stopIntercepting();
                $interceptedWithUniquePayload = InterceptingMiddleware::getInterceptedMessagesWithUniquePayload();

                // Important! Only publish the intercepted messages if there are actually any. Otherwise the EventBus
                // service will be instantiated for requests that do not require it, which in turn will trigger the
                // command bus to be instantiated. And the command bus requires the current user id to work, which is
                // not available on all requests (for example OPTIONS requests, or public GET requests).
                if ($interceptedWithUniquePayload->getIterator()->count() > 0) {
                    /** @var EventBus $eventBus */
                    $eventBus = $app[EventBus::class];
                    $eventBus->publish($interceptedWithUniquePayload);
                }
            }
        );
    }
}
