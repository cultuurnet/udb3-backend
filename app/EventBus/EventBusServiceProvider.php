<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Broadway\AMQP\AMQPPublisher;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\History\HistoryProjector as EventHistoryProjector;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsProjector;
use CultuurNet\UDB3\Event\RelocateEventToCanonicalPlace;
use CultuurNet\UDB3\EventBus\Middleware\CallbackOnFirstPublicationMiddleware;
use CultuurNet\UDB3\EventBus\Middleware\InterceptingMiddleware;
use CultuurNet\UDB3\EventBus\Middleware\ReplayFlaggingMiddleware;
use CultuurNet\UDB3\Label\ReadModels\JSON\LabelVisibilityOnRelatedDocumentsProjector;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Offer\ProcessManagers\AutoApproveForUiTIDv1ApiKeysProcessManager;
use CultuurNet\UDB3\Offer\ProcessManagers\RelatedDocumentProjectedToJSONLDDispatcher;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataProjector;
use CultuurNet\UDB3\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerPermissionServiceProvider;
use CultuurNet\UDB3\Ownership\Readmodels\OwnershipLDProjector;
use CultuurNet\UDB3\Ownership\Readmodels\OwnershipSearchProjector;
use CultuurNet\UDB3\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Place\ReadModel\History\HistoryProjector as PlaceHistoryProjector;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsProjector;
use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;

final class EventBusServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            EventBus::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            EventBus::class,
            function () use ($container): EventBus {
                $eventBus = new MiddlewareEventBus();

                $callbackMiddleware = new CallbackOnFirstPublicationMiddleware(
                    function () use (&$eventBus, $container): void {
                        $subscribers = [
                            EventRelationsProjector::class,
                            PlaceRelationsProjector::class,
                            EventJSONLDServiceProvider::PROJECTOR,
                            EventHistoryProjector::class,
                            PlaceHistoryProjector::class,
                            PlaceJSONLDServiceProvider::PROJECTOR,
                            OrganizerJSONLDServiceProvider::PROJECTOR,
                            RelatedDocumentProjectedToJSONLDDispatcher::class,
                            'event_calendar_projector',
                            'event_permission.projector',
                            'place_permission.projector',
                            OrganizerPermissionServiceProvider::PERMISSION_PROJECTOR,
                            AMQPPublisher::class,
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
                            RelocateEventToCanonicalPlace::class,
                            AutoApproveForUiTIDv1ApiKeysProcessManager::class,
                            OwnershipLDProjector::class,
                            OwnershipSearchProjector::class,
                        ];

                        $initialSubscribersCount = count($subscribers);
                        $subscribers = array_unique($subscribers);
                        if ($initialSubscribersCount != count($subscribers)) {
                            throw new \Exception('Some projectors are subscribed more then once!');
                        }

                        foreach ($subscribers as $subscriberServiceId) {
                            $eventBus->subscribe($container->get($subscriberServiceId));
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
}
