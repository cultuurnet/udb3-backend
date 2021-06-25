<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Silex;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtAuthenticationProvider;
use CultuurNet\UDB3\Jwt\Symfony\Firewall\JwtListener;
use CultuurNet\UDB3\Jwt\FallbackJwtDecoder;
use CultuurNet\UDB3\Jwt\JwtDecoderService;
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
                $app['security.token_decoder.' . $name . '.jwt'] = $app->share(
                    function () use ($options) {
                        return new FallbackJwtDecoder(
                            new JwtDecoderService(
                                new Parser(),
                                new Sha256(),
                                new Key($options['uitid']['public_key']),
                                $options['uitid']['required_claims'],
                                $options['uitid']['valid_issuers']
                            ),
                            new JwtDecoderService(
                                new Parser(),
                                new Sha256(),
                                new Key($options['auth0']['public_key']),
                                $options['auth0']['required_claims'],
                                $options['auth0']['valid_issuers']
                            )
                        );
                    }
                );

                // define the authentication provider object
                $app['security.authentication_provider.' . $name . '.jwt'] = $app->share(
                    function () use ($app, $name) {
                        return new JwtAuthenticationProvider(
                            $app['security.token_decoder.' . $name . '.jwt'],
                            $app['config']['jwt']['auth0']['jwt_provider_client_id']
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
