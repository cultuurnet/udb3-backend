<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UiTIDProvider\Security\MultiPathRequestMatcher;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Event\EventLabellerServiceInterface;
use CultuurNet\UDB3\Event\Title;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

/**
 * Allow to use services as controllers.
 */
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

/**
 * Firewall configuration.
 *
 * We can not expect the UUID of events, places and organizers
 * to be correctly formatted, because there is no exhaustive documentation
 * about how this is handled in UDB2. Therefore we take a rather liberal
 * approach and allow all alphanumeric characters and a dash.
 */
$app['id_pattern'] = '[\w\-]+';
$app['security.firewalls'] = array(
  'authentication' => array(
    'pattern' => '^/culturefeed/oauth',
  ),
  'public' => array(
    'pattern' => new MultiPathRequestMatcher(
        [
              '^/api/1.0/event.jsonld',
              '^/(event|place)/'.$app['id_pattern'].'$',
              '^/event/'.$app['id_pattern'].'/history',
              '^/organizer/'.$app['id_pattern'],
              '^/media/'.$app['id_pattern'].'$',
              '^/places$',
              '^/api/1.0/organizer/suggest/.*'
        ]
    )
  ),
  'entryapi' => array(
    'pattern' => '^/rest/entry/.*',
    'oauth' => true,
    'stateless' => true,
  ),
  'cors-preflight' => array(
    'pattern' => $app['cors_preflight_request_matcher'],
  ),
  'secured' => array(
    'pattern' => '^.*$',
    'uitid' => [
      'roles' => isset($app['config']['roles']) ? $app['config']['roles'] : [],
    ],
    'users' => $app['uitid_firewall_user_provider'],
  ),
);

/**
 * Security services.
 */
$app->register(new \Silex\Provider\SecurityServiceProvider());
$app->register(new \CultuurNet\UiTIDProvider\Security\UiTIDSecurityServiceProvider());

require __DIR__ . '/../debug.php';

$app['logger.search'] = $app->share(
    function ($app) {
        $logger = new \Monolog\Logger('search');

        $handlers = $app['config']['log.search'];
        foreach ($handlers as $handler_config) {
            switch ($handler_config['type']) {
                case 'hipchat':
                    $handler = new \Monolog\Handler\HipChatHandler(
                        $handler_config['token'],
                        $handler_config['room']
                    );
                    break;
                case 'file':
                    $handler = new \Monolog\Handler\StreamHandler(
                        __DIR__ . '/' . $handler_config['path']
                    );
                    break;
                default:
                    continue 2;
            }

            $handler->setLevel($handler_config['level']);
            $logger->pushHandler($handler);
        }

        return $logger;
    }
);

// Enable CORS.
$app->after($app["cors"]);

$app->before(
    function (Request $request) {
        if (0 === strpos(
                $request->headers->get('Content-Type'),
                'application/json'
            )
        ) {
            $data = json_decode($request->getContent(), true);
            if (NULL === $data) {
                // Decoding failed. Probably the submitted JSON is not correct.
                return Response::create('Unable to decode the submitted body. Is it valid JSON?', 400);
            }
            $request->request->replace(is_array($data) ? $data : array());
        }
    }
);

/**
 * Bootstrap metadata based on user context.
 */
$app->before(
    function (Request $request, Application $app) {
        $contextValues = [];

        $contextValues['client_ip'] = $request->getClientIp();
        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

        /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage $tokenStorage */
        $tokenStorage = $app['security.token_storage'];
        $authToken = $tokenStorage->getToken();

        // Web service consumer authenticated with OAuth.
        if ($authToken instanceof \CultuurNet\SymfonySecurityOAuth\Security\OAuthToken &&
            $authToken->isAuthenticated()
        ) {
            $contextValues['uitid_token_credentials'] = new \CultuurNet\Auth\TokenCredentials(
                $authToken->getAccessToken()->getToken(),
                $authToken->getAccessToken()->getSecret()
            );
            $contextValues['consumer'] = [
                'key' => $authToken->getAccessToken()->getConsumer()->getConsumerKey(),
                'secret' => $authToken->getAccessToken()->getConsumer()->getConsumerSecret(),
                'name' => $authToken->getAccessToken()->getConsumer()->getName()
            ];
            $user = $authToken->getUser();

            if ($user instanceof \CultuurNet\SymfonySecurityOAuthUitid\User) {
                $contextValues['user_id'] = $user->getUid();
                $contextValues['user_nick'] = $user->getUsername();
                $contextValues['user_email'] = $user->getEmail();
            }
        } else if ($app['uitid_user']) {
            $contextValues['uitid_token_credentials'] = $app['culturefeed_token_credentials'];
            /** @var \CultureFeed_User $user */
            $user = $app['uitid_user'];
            $contextValues['user_id'] = $user->id;
            $contextValues['user_nick'] = $user->nick;
            $contextValues['user_email'] = $user->mbox;
        }

        $contextValues['client_ip'] = $request->getClientIp();
        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

        /** @var \CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher $metadataEnricher */
        $metadataEnricher = $app['execution_context_metadata_enricher'];
        $metadataEnricher->setContext(new \Broadway\Domain\Metadata($contextValues));
    }
);

$app->get(
    'api/1.0/event.jsonld',
    function (Request $request, Application $app) {
        $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse(
            'api/1.0/event.jsonld'
        );
        $response->headers->set('Content-Type', 'application/ld+json');
        return $response;
    }
);

$app
    ->post(
        'event/{cdbid}/{lang}/title',
        function (Request $request, Application $app, $cdbid, $lang) {
            /** @var \CultuurNet\UDB3\Event\EventEditingServiceInterface $service|\CultuurNet\UDB3\Offer\OfferEditingServiceInterface $service */
            $service = $app['event_editor'];

            $response = new JsonResponse();

            $title = $request->request->get('title');
            if (!$title) {
                return new JsonResponse(['error' => "title required"], 400);
            }

            try {
                $commandId = $service->translateTitle(
                    $cdbid,
                    new \CultuurNet\UDB3\Language($lang),
                    new \ValueObjects\String\String($title)
                );

                $response->setData(['commandId' => $commandId]);
            } catch (Exception $e) {
                $response->setStatusCode(400);
                $response->setData(['error' => $e->getMessage()]);
            }

            return $response;
        }
    );

$app
    ->post(
        'event/{cdbid}/labels',
        function (Request $request, Application $app, $cdbid) {
            /** @var \CultuurNet\UDB3\Event\EventEditingServiceInterface|\CultuurNet\UDB3\Offer\OfferEditingServiceInterface $service */
            $service = $app['event_editor'];

            $response = new JsonResponse();

            $label = new \CultuurNet\UDB3\Label($request->request->get('label'));
            $commandId = $service->addLabel(
                $cdbid,
                $label
            );

            /** @var CultureFeed_User $user */
            $user = $app['current_user'];
            $app['used_labels_memory']->rememberLabelUsed(
                $user->id,
                $label
            );

            $response->setData(['commandId' => $commandId]);

            return $response;
        }
    );

$app
    ->delete(
        'event/{cdbid}/labels/{label}',
        function (Request $request, Application $app, $cdbid, $label) {
            /** @var \CultuurNet\UDB3\Event\EventEditingServiceInterface|\CultuurNet\UDB3\Offer\OfferEditingServiceInterface $service */
            $service = $app['event_editor'];

            $response = new JsonResponse();

            $commandId = $service->deleteLabel(
                $cdbid,
                new \CultuurNet\UDB3\Label($label)
            );

            $response->setData(['commandId' => $commandId]);

            return $response;
        }
    );

$app
    ->post(
        'place/{cdbid}/labels',
        function (Request $request, Application $app, $cdbid) {
            /** @var \CultuurNet\UDB3\Place\PlaceEditingServiceInterface|\CultuurNet\UDB3\Offer\OfferEditingServiceInterface $service */
            $service = $app['place_editing_service'];

            $response = new JsonResponse();

            $label = new \CultuurNet\UDB3\Label($request->request->get('label'));

            $commandId = $service->addLabel(
                $cdbid,
                $label
            );

            /** @var CultureFeed_User $user */
            $user = $app['current_user'];
            $app['used_labels_memory']->rememberLabelUsed(
                $user->id,
                $label
            );

            $response->setData(['commandId' => $commandId]);

            return $response;
        }
    );

$app
    ->delete(
        'place/{cdbid}/labels/{label}',
        function (Request $request, Application $app, $cdbid, $label) {
            /** @var \CultuurNet\UDB3\Place\PlaceEditingServiceInterface|\CultuurNet\UDB3\Offer\OfferEditingServiceInterface $service */
            $service = $app['place_editing_service'];

            $response = new JsonResponse();

            $commandId = $service->deleteLabel(
                $cdbid,
                new \CultuurNet\UDB3\Label($label)
            );

            $response->setData(['commandId' => $commandId]);

            return $response;
        }
    );

$app
    ->post(
        'place/{cdbid}/{lang}/title',
        function (Request $request, Application $app, $cdbid, $lang) {
            /** @var \CultuurNet\UDB3\Place\PlaceEditingServiceInterface|\CultuurNet\UDB3\Offer\OfferEditingServiceInterface $service */
            $service = $app['place_editing_service'];

            $response = new JsonResponse();

            $title = $request->request->get('title');
            if (!$title) {
                return new JsonResponse(['error' => "title required"], 400);
            }

            try {
                $commandId = $service->translateTitle(
                    $cdbid,
                    new \CultuurNet\UDB3\Language($lang),
                    new \ValueObjects\String\String($title)
                );

                $response->setData(['commandId' => $commandId]);
            } catch (Exception $e) {
                $response->setStatusCode(400);
                $response->setData(['error' => $e->getMessage()]);
            }

            return $response;
        }
    );

$app
    ->post(
        'place/{cdbid}/{lang}/description',
        function (Request $request, Application $app, $cdbid, $lang) {
            /** @var \CultuurNet\UDB3\Place\PlaceEditingServiceInterface|\CultuurNet\UDB3\Offer\OfferEditingServiceInterface $service */
            $service = $app['place_editing_service'];

            $response = new JsonResponse();

            $description = $request->request->get('description');
            if (!$description) {
                return new JsonResponse(['error' => "description required"], 400);
            }

            try {
                $commandId = $service->translateDescription(
                    $cdbid,
                    new \CultuurNet\UDB3\Language($lang),
                    new \ValueObjects\String\String($request->get('description'))
                );

                $response->setData(['commandId' => $commandId]);
            } catch (\Exception $e) {
                $response->setStatusCode(400);
                $response->setData(['error' => $e->getMessage()]);
            }

            return $response;
        }
    );

$app->get(
    'api/1.0/user/labels',
    function (Request $request, Application $app) {
        /** @var \CultuurNet\UDB3\UsedLabelsMemory\UsedLabelsMemoryServiceInterface $usedLabelsMemoryService */
        $usedLabelsMemoryService = $app['used_labels_memory'];
        $user = $app['current_user'];
        $memory = $usedLabelsMemoryService->getMemory($user->id);

        return JsonResponse::create($memory);
    }
);

$app->post(
    'events/label',
    function (Request $request, Application $app) {
        /** @var EventLabellerServiceInterface $eventLabeller */
        $eventLabeller = $app['event_labeller'];

        $label = new \CultuurNet\UDB3\Label($request->request->get('label'));
        $eventIds = $request->request->get('events');

        $response = new JsonResponse();

        $commandId = $eventLabeller->labelEventsById($eventIds, $label);

        /** @var CultureFeed_User $user */
        $user = $app['current_user'];
        $app['used_labels_memory']->rememberLabelUsed(
            $user->id,
            $label
        );

        $response->setData(['commandId' => $commandId]);

        return $response;
    }
);

$app->post('query/label',
    function (Request $request, Application $app) {
        /** @var EventLabellerServiceInterface $eventLabeller */
        $eventLabeller = $app['event_labeller'];

        $query = $request->request->get('query', false);
        if (!$query) {
            return new JsonResponse(['error' => "query required"], 400);
        }

        $label = new \CultuurNet\UDB3\Label($request->request->get('label'));
        $commandId = $eventLabeller->labelQuery($query, $label);

        /** @var CultureFeed_User $user */
        $user = $app['current_user'];
        $app['used_labels_memory']->rememberLabelUsed(
            $user->id,
            $label
        );

        return new JsonResponse(['commandId' => $commandId]);
    });

$app->mount('events/export', new \CultuurNet\UDB3\Silex\Export\ExportControllerProvider());

$app->get(
    'swagger.json',
    function (Request $request) {
        $file = new SplFileInfo(__DIR__ . '/swagger.json');
        return new \Symfony\Component\HttpFoundation\BinaryFileResponse(
            $file,
            200,
            [
                'Content-Type' => 'application/json',
            ]
        );
    }
);

$app->mount('saved-searches', new \CultuurNet\UDB3\Silex\SavedSearches\SavedSearchesControllerProvider());

$app->mount('variations', new \CultuurNet\UDB3\Silex\Variations\VariationsControllerProvider());

$app->mount('rest/entry', new \CultuurNet\UDB3SilexEntryAPI\EventControllerProvider());

$app->register(new \CultuurNet\UDB3\Silex\ErrorHandlerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Search\SearchControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Place\PlaceControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Organizer\OrganizerControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Event\EventControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Media\MediaControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\User\UserControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\OfferControllerProvider());

/**
 * API callbacks for authentication.
 */
$app->mount('culturefeed/oauth', new \CultuurNet\UiTIDProvider\Auth\AuthControllerProvider());

/**
 * API callbacks for UiTID user data and methods.
 */
$app->mount('uitid', new \CultuurNet\UiTIDProvider\User\UserControllerProvider());

/**
 * Basic REST API for feature toggles.
 */
$app->mount('/', new \TwoDotsTwice\SilexFeatureToggles\FeatureTogglesControllerProvider());

/**
 * Dummy endpoint implementations. Make sure you keep this as the last one,
 * already implemented routes will not be overridden.
 */
$app->mount('/', new \CultuurNet\UDB3\Silex\DummyControllerProvider());

$app->run();
