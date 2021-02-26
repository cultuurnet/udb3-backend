<?php

namespace CultuurNet\UDB3\Jwt\Silex;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtAuthenticationProvider;
use CultuurNet\UDB3\Jwt\Symfony\Firewall\JwtListener;
use CultuurNet\UDB3\Jwt\FallbackJwtDecoder;
use CultuurNet\UDB3\Jwt\JwtDecoderService;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\ValidationData;
use Silex\Application;
use Silex\ServiceProviderInterface;

class JwtServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['security.authentication_listener.factory.jwt'] = $app->protect(
            function ($name, $options) use ($app) {
                $validationData = function (array $claims) {
                    $validationData = new ValidationData();
                    foreach ($claims as $claim => $value) {
                        switch ($claim) {
                            case 'jti':
                                $validationData->setId($value);
                                break;
                            case 'iss':
                                $validationData->setIssuer($value);
                                break;
                            case 'aud':
                                $validationData->setAudience($value);
                                break;
                            case 'sub':
                                $validationData->setSubject($value);
                                break;
                            case 'current_time':
                                $validationData->setCurrentTime($value);
                                break;
                        }
                    }
                    return $validationData;
                };
                $app['security.token_decoder.' . $name . '.jwt'] = $app->share(
                    function (Application $app) use ($options, $validationData) {
                        return new FallbackJwtDecoder(
                            new JwtDecoderService(
                                new Parser(),
                                $validationData($options['uitid']['validation'] ?? []),
                                new Sha256(),
                                new Key($options['uitid']['public_key']),
                                $options['uitid']['required_claims']
                            ),
                            new JwtDecoderService(
                                new Parser(),
                                $validationData($options['auth0']['validation'] ?? []),
                                new Sha256(),
                                new Key($options['auth0']['public_key']),
                                $options['auth0']['required_claims']
                            )
                        );
                    }
                );

                // define the authentication provider object
                $app['security.authentication_provider.' . $name . '.jwt'] = $app->share(
                    function () use ($app, $name) {
                        return new JwtAuthenticationProvider(
                            $app['security.token_decoder.' . $name . '.jwt']
                        );
                    }
                );

                // define the authentication listener object
                $app['security.authentication_listener.' . $name . '.jwt'] = $app->share(
                    function () use ($app, $name) {
                        return new JwtListener(
                            $app['security.token_storage'],
                            $app['security.authentication_manager'],
                            $app['security.token_decoder.' . $name . '.jwt']
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
