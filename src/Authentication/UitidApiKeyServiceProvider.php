<?php


namespace CultuurNet\UDB3\Silex\Authentication;

use CultuurNet\UDB3\ApiGuard\ApiKey\AllowAnyAuthenticator;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

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

        $app['auth.api_key_authenticator'] = $app->share(
            function (Application $app) {
                return new CultureFeedApiKeyAuthenticator(
                    $app['culturefeed'],
                    $app['auth.consumer_repository']
                );
            }
        );

        $app['auth.any_api_key_authenticator'] = $app->share(
            function () {
                return new AllowAnyAuthenticator();
            }
        );

        $app['auth.request_authenticator'] = $app->share(
            function (Application $app) {
                return new ApiKeyRequestAuthenticator(
                    $app['auth.api_key_reader'],
                    $app['auth.any_api_key_authenticator']
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

                    try {
                        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
                            $apiKeyAuthenticator->authenticate($request);
                        }
                    } catch (AuthenticationCredentialsNotFoundException $exception) {
                        // The authentication credentials are missing because the URL is not secured.
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
