<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Authentication;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\CultureFeedConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Auth\RequestAuthenticator;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UitidApiKeyServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['auth.api_key_reader'] = $app->share(
            function () {
                $queryReader = new QueryParameterApiKeyReader('apiKey');
                $headerReader = new CustomHeaderApiKeyReader('X-Api-Key');

                return new CompositeApiKeyReader(
                    $queryReader,
                    $headerReader
                );
            }
        );

        $app['auth.consumer_repository'] = $app->share(
            function (Application $app): ConsumerReadRepository {
                return new InMemoryConsumerRepository(
                    new CultureFeedConsumerReadRepository($app['culturefeed'], true)
                );
            }
        );

        $app['auth.api_key_authenticator'] = $app->share(
            function (Application $app) {
                return new CultureFeedApiKeyAuthenticator($app['auth.consumer_repository']);
            }
        );

        $app['auth.request_authenticator'] = $app->share(
            function (Application $app) {
                return new ApiKeyRequestAuthenticator(
                    $app['auth.api_key_reader'],
                    $app['auth.api_key_authenticator']
                );
            }
        );

        $app['consumer'] = null;

        $app->before(
            function (Request $request, Application $app): ?Response {
                if ($app['auth.api_key_bypass']) {
                    return null;
                }

                // Don't do an API key check if an access token with Auth0 client id is used.
                if (!is_null($app['api_client_id'])) {
                    return null;
                }

                /** @var RequestAuthenticator $requestAuthenticator */
                $requestAuthenticator = $app[RequestAuthenticator::class];
                /** @var ApiKeyRequestAuthenticator $apiKeyAuthenticator */
                $apiKeyAuthenticator = $app['auth.request_authenticator'];

                $psr7Request = (new DiactorosFactory())->createRequest($request);
                if ($requestAuthenticator->isPublicRoute($psr7Request)) {
                    // The request is for a public URL so we can skip any checks.
                    return null;
                }

                // Also store the ApiKey for later use in the impersonator.
                $app['auth.api_key'] = $app['auth.api_key_reader']->read($psr7Request);

                try {
                    $apiKeyAuthenticator->authenticate($psr7Request);
                } catch (RequestAuthenticationException $e) {
                    return (new ApiProblemJsonResponse(ApiProblem::unauthorized($e->getMessage())))
                        ->toHttpFoundationResponse();
                }

                // Check that the API consumer linked to the API key has the required permission to use EntryAPI.
                $permissionCheck = new ConsumerIsInPermissionGroup(
                    (string) $app['auth.api_key.group_id']
                );

                /* @var ConsumerReadRepository $consumerRepository */
                $consumerRepository = $app['auth.consumer_repository'];
                /** @var Consumer $consumer */
                $consumer = $consumerRepository->getConsumer($app['auth.api_key']);

                if (!$permissionCheck->satisfiedBy($consumer)) {
                    return (new ApiProblemJsonResponse(
                        ApiProblem::forbidden('Given API key is not authorized to use EntryAPI.')
                    ))->toHttpFoundationResponse();
                }

                $app['consumer'] = $consumer;
                return null;
            },
            Application::LATE_EVENT
        );
    }


    public function boot(Application $app)
    {
    }
}
