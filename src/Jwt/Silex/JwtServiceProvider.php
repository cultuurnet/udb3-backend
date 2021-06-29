<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Silex;

use CultuurNet\UDB3\Jwt\JwtV2Validator;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtAuthenticationProvider;
use CultuurNet\UDB3\Jwt\Symfony\Firewall\JwtListener;
use CultuurNet\UDB3\Jwt\JwtBaseValidator;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Silex\Application;
use Silex\ServiceProviderInterface;

class JwtServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['security.authentication_listener.factory.jwt'] = $app->protect(
            function ($name, $options) use ($app) {
                // define the authentication provider object
                $app['security.authentication_provider.' . $name . '.jwt'] = $app->share(
                    function () use ($app, $options) {
                        return new JwtAuthenticationProvider(
                            new JwtBaseValidator(
                                new Sha256(),
                                new Key($options['v1']['public_key']),
                                $options['v1']['required_claims'],
                                $options['v1']['valid_issuers']
                            ),
                            new JwtV2Validator(
                                new JwtBaseValidator(
                                    new Sha256(),
                                    new Key($options['v2']['public_key']),
                                    $options['v2']['required_claims'],
                                    $options['v2']['valid_issuers']
                                ),
                                $app['config']['jwt']['v2']['jwt_provider_client_id']
                            )
                        );
                    }
                );

                // define the authentication listener object
                $app['security.authentication_listener.' . $name . '.jwt'] = $app->share(
                    function () use ($app) {
                        return new JwtListener(
                            $app['security.token_storage'],
                            $app['security.authentication_manager'],
                            new Parser()
                        );
                    }
                );

                return [
                    // the authentication provider id
                    'security.authentication_provider.' . $name . '.jwt',
                    // the authentication listener id
                    'security.authentication_listener.' . $name . '.jwt',
                    // the entry point id
                    null,
                    // the position of the listener in the stack
                    'pre_auth',
                ];
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
