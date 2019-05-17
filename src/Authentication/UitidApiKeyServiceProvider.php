<?php


namespace CultuurNet\UDB3\Silex\Authentication;

use CultuurNet\UDB3\ApiGuard\ApiKey\AllowAnyAuthenticator;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\Silex\Impersonator;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use ValueObjects\StringLiteral\StringLiteral;

class UitidApiKeyServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
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
            function (Application $app) {
                return new InMemoryConsumerRepository();
            }
        );

        $app['auth.api_key_authenticator'] = $app->share(
            function (Application $app) {
                return new CultureFeedApiKeyAuthenticator(
                    $app['culturefeed'],
                    $app['auth.consumer_repository']
                );
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

        /** @var ToggleManager $toggles */
        $toggles = $app['toggles'];

        if ($toggles->active('uitid-api-key-required', $app['toggles.context'])) {
            $app->before(
                function (Request $request, Application $app) {
                    /** @var AuthorizationChecker $security */
                    $security = $app['security.authorization_checker'];
                    /** @var ApiKeyRequestAuthenticator $apiKeyAuthenticator */
                    $apiKeyAuthenticator = $app['auth.request_authenticator'];

                    // Also store the ApiKey for later use in the impersonator.
                    $app['auth.api_key'] = $app['auth.api_key_reader']->read($request);

                    try {
                        if (!$security->isGranted('IS_AUTHENTICATED_FULLY')) {
                            // The request is not authenticated so we don't need to do additional checks since the
                            // firewall will return an unauthorized error response.
                            return;
                        }
                    } catch (AuthenticationCredentialsNotFoundException $exception) {
                        // The request is for a public URL so we can skip any checks.
                        return;
                    }

                    $apiKeyAuthenticator->authenticate($request);

                    // Check that the API consumer linked to the API key has the required permission to use EntryAPI.
                    $permissionCheck = new ConsumerIsInPermissionGroup(
                        new StringLiteral((string) $app['auth.api_key.group_id'])
                    );

                    /* @var ConsumerReadRepositoryInterface $consumerRepository */
                    $consumerRepository = $app['auth.consumer_repository'];
                    $consumer = $consumerRepository->getConsumer($app['auth.api_key']);

                    if (!$permissionCheck->satisfiedBy($consumer)) {
                        throw new RequestAuthenticationException('Given API key is not authorized to use EntryAPI.');
                    }
                },
                Application::LATE_EVENT
            );
        }
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
