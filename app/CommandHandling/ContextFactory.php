<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\User\CurrentUser;
use Psr\Container\ContainerInterface;

final class ContextFactory
{
    public static function createContext(
        ?string $userId = null,
        ?JsonWebToken $jwt = null,
        ?ApiKey $apiKey = null,
        ?string $apiName = null,
        ?Consumer $consumer = null
    ): Metadata {
        $contextValues = [];

        if ($userId) {
            $contextValues['user_id'] = $userId;
        }

        if ($jwt) {
            $contextValues['auth_jwt'] = $jwt->getCredentials();
        }

        if ($jwt && $jwt->getClientId()) {
            $contextValues['auth_api_client_id'] = $jwt->getClientId();
        }

        if ($jwt && $jwt->getClientName()) {
            $contextValues['auth_api_client_name'] = $jwt->getClientName();
        }

        if ($apiKey) {
            $contextValues['auth_api_key'] = $apiKey;
        }

        if ($apiName) {
            $contextValues['api'] = $apiName;
        }

        if ($consumer) {
            $contextValues['consumer']['name'] = $consumer->getName();
        }

        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

        return new Metadata($contextValues);
    }

    public static function prepareForLogging(Metadata $metadata): Metadata
    {
        $metadata = $metadata->serialize();

        // Don't store the JWT when logging the metadata in the event store.
        unset($metadata['auth_jwt']);

        // Convert the ApiKey object to a string so it can get JSON-encoded.
        if (isset($metadata['auth_api_key'])) {
            $metadata['auth_api_key'] = $metadata['auth_api_key']->toString();
        }

        return new Metadata($metadata);
    }

    public static function createFromGlobals(ContainerInterface $container): Metadata
    {
        return self::createContext(
            $container->get(CurrentUser::class)->getId(),
            $container->get(JsonWebToken::class),
            $container->get(ApiKey::class),
            $container->get(ApiName::class),
            $container->get(Consumer::class)
        );
    }
}
