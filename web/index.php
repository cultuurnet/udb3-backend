<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\SymfonySecurityJwt\Authentication\JwtUserToken;
use CultuurNet\UDB3\HttpFoundation\RequestMatcher\AnyOfRequestMatcher;
use CultuurNet\UDB3\HttpFoundation\RequestMatcher\PreflightRequestMatcher;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Silex\FeatureControllerProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Symfony\Management\PermissionsVoter;
use CultuurNet\UDB3\Symfony\Management\UserPermissionsVoter;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

/**
 * @SWG\Swagger(
 *     basePath="/",
 *     host="io.uitdatabank.be",
 *     schemes={"http"},
 *     produces={"application/json"},
 *     consumes={"application/json"},
 *     @SWG\Info(
 *         version="3.0.0",
 *         title="UiTdatabank v3",
 *         description="Version 3 of the UiTdatabank, a central database of cultural offers in the Flanders region.\n Most operations require that you authenticate first with your UiTID.",
 *         @SWG\Contact(
 *               name="CultuurNet Vlaanderen vzw",
 *               url="http://www.cultuurnet.be",
 *               email="info@uitdatabank.be"
 *         )
 *     )
 * )
 */

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
$app['cors_preflight_request_matcher'] = $app->share(
    function () {
        return new PreflightRequestMatcher();
    }
);

$app['id_pattern'] = '[\w\-]+';
$app['security.firewalls'] = array(
    'public' => [
        'pattern' => (new AnyOfRequestMatcher())
            ->with(new RequestMatcher('^/contexts/.*', null, 'GET'))
            ->with(new RequestMatcher('^/(event|place|label)/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(event|place)/' . $app['id_pattern'] . '/permission/.+$', null, 'GET'))
            ->with(new RequestMatcher('^/event/' . $app['id_pattern'] . '/history', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/' . $app['id_pattern'], null, 'GET'))
            ->with(new RequestMatcher('^/media/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(places|labels)$', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/suggest/.*', null, 'GET'))
            ->with(new RequestMatcher('^/jobs/', null, 'GET'))
    ],
    'cors-preflight' => array(
        'pattern' => $app['cors_preflight_request_matcher'],
    ),
    'secured' => array(
        'pattern' => '^.*$',
        'jwt' => [
            'validation' => $app['config']['jwt']['validation'],
            'required_claims' => [
                'uid',
                'nick',
                'email',
            ],
            'public_key' => 'file://' . __DIR__ . '/../' . $app['config']['jwt']['keys']['public']['file'],
        ],
        'stateless' => true,
    ),
);

/**
 * Security services.
 */
$app->register(new \Silex\Provider\SecurityServiceProvider());
$app->register(new \CultuurNet\SilexServiceProviderJwt\JwtServiceProvider());

$app['permissions_voter'] = $app->share(function($app) {
    return new PermissionsVoter($app['config']['user_permissions']);
});

$app['user_permissions_voter'] = $app->share(function($app) {
    return new UserPermissionsVoter($app[UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY]);
});

$app['security.voters'] = $app->extend('security.voters', function($voters) use ($app){
    return array_merge(
        $voters,
        [
            $app['permissions_voter'],
            $app['user_permissions_voter'],
        ]
    );
});

$app['security.access_manager'] = $app->share(function($app) {
    return new AccessDecisionManager($app['security.voters'], AccessDecisionManager::STRATEGY_AFFIRMATIVE);
});

$app['security.access_rules'] = array(
    array(
        new RequestMatcher('^/labels/.*', null, ['POST', 'DELETE', 'PATCH']),
        Permission::LABELS_BEHEREN
    ),
    array('^/(roles|permissions|users)/.*', Permission::GEBRUIKERS_BEHEREN),
);

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

if (isset($app['config']['cdbxml_proxy']) &&
    $app['config']['cdbxml_proxy']['enabled']) {
    $app->before(
        function (Request $request, Application $app) {
            /** @var \CultuurNet\UDB3\Symfony\Proxy\CdbXmlProxy $cdbXmlProxy */
            $cdbXmlProxy = $app['cdbxml_proxy'];

            return $cdbXmlProxy->handle($request);
        },
        Application::EARLY_EVENT
    );
}

if (isset($app['config']['calendar_summary_proxy']) &&
    $app['config']['calendar_summary_proxy']['enabled']) {
    $app->before(
        function (Request $request, Application $app) {
            /** @var \CultuurNet\UDB3\Symfony\Proxy\CalendarSummaryProxy $calendarSummaryProxy */
            $calendarSummaryProxy = $app['calendar_summary_proxy'];

            return $calendarSummaryProxy->handle($request);
        },
        Application::EARLY_EVENT
    );
}

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

        if ($authToken instanceof JwtUserToken && $authToken->isAuthenticated()) {
            $jwt = $authToken->getCredentials();
            $contextValues['user_id'] = $jwt->getClaim('uid');
            $contextValues['user_nick'] = $jwt->getClaim('nick');
            $contextValues['user_email'] = $jwt->getClaim('email');
        }

        $contextValues['client_ip'] = $request->getClientIp();
        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

        /** @var \CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher $metadataEnricher */
        $metadataEnricher = $app['execution_context_metadata_enricher'];
        $metadataEnricher->setContext(new \Broadway\Domain\Metadata($contextValues));
    }
);

$app->mount('events/export', new \CultuurNet\UDB3\Silex\Export\ExportControllerProvider());

$app->get(
    'swagger.json',
    function () {
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

$app->mount(
    'variations',
    new FeatureControllerProvider(
        'variations',
        new \CultuurNet\UDB3\Silex\Variations\VariationsControllerProvider()
    )
);

$app->register(new \CultuurNet\UDB3\Silex\ErrorHandlerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Search\SAPISearchControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Place\PlaceControllerProvider());
$app->mount('/organizers', new \CultuurNet\UDB3\Silex\Organizer\OrganizerControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Event\EventControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Media\MediaControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\OfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\User\UserControllerProvider());
$app->mount('dashboard/', new \CultuurNet\UDB3\Silex\Dashboard\DashboardControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Role\RoleControllerProvider());
$app->mount('/labels', new \CultuurNet\UDB3\Silex\Labels\LabelsControllerProvider());
$app->mount('/jobs', new \CultuurNet\UDB3\Silex\Jobs\JobsControllerProvider());
$app->mount('/contexts', new \CultuurNet\UDB3\Silex\JSONLD\ContextControllerProvider());

/**
 * @SWG\Get(
 *     path="/user",
 *     summary="Retrieve all information about the authenticated user.",
 *     description="Authentication is required.",
 *     operationId="getUserInformation",
 *     produces={"application/json"},
 *     @SWG\Response(
 *         response="200",
 *         description="The information available about the authenticated user.",
 *         @SWG\Schema(ref="#/definitions/UserInformation")
 *     ),
 *     @SWG\Response(
 *         response="401",
 *         ref="#/responses/Unauthorized"
 *     ),
 *     tags={"User"}
 * )
 */
$app->get(
    '/user',
    function (Application $app) {
        return (new JsonResponse())
            /**
             * @SWG\Definition(
             *     definition="UserInformation",
             *     @SWG\Property(
             *         property="id",
             *         format="uuid",
             *         type="string",
             *         description="A universally unique identifier.",
             *         example="6f072ba8-c510-40ac-b387-51f582650e27"
             *     ),
             *     @SWG\Property(
             *         property="nick",
             *         type="string",
             *         example="El Pistolero"
             *     ),
             *     required={"id", "nick"},
             * )
             */
            ->setData((object)[
                'id' => $app['current_user']->id,
                'nick' => $app['current_user']->nick,
            ])
            ->setPrivate();
    }
);

/**
 * Basic REST API for feature toggles.
 */
$app->mount('/', new \TwoDotsTwice\SilexFeatureToggles\FeatureTogglesControllerProvider());

$app->mount('/', new \CultuurNet\UDB3\Silex\Moderation\ModerationControllerProvider());

$app->run();
