<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\Domain\Metadata;
use CultureFeed_User;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ContextFactory
{
    public static function createContext(
        ?CultureFeed_User $user = null,
        ?Udb3Token $jwt = null,
        ?ApiKey $apiKey = null,
        ?string $apiName = null,
        ?Request $request = null,
        ?ConsumerInterface $consumer = null
    ): Metadata {
        $contextValues = [];

        if ($user) {
            $contextValues['user_id'] = $user->id;
            $contextValues['user_nick'] = $user->nick;
            $contextValues['user_email'] = $user->mbox;
        }

        if ($jwt) {
            $contextValues['auth_jwt'] = $jwt;
        }

        if ($apiKey) {
            $contextValues['auth_api_key'] = $apiKey;
        }

        if ($apiName) {
            $contextValues['api'] = $apiName;
        }

        if ($request) {
            $contextValues['client_ip'] = $request->getClientIp();
        }
        if ($consumer) {
            $contextValues['consumer']['name'] = $consumer->getName();
        }

        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

        return new Metadata($contextValues);
    }

    public static function createFromGlobals(Application $application): Metadata
    {
        $request = null;
        $requestStack = $application['request_stack'];
        if ($requestStack instanceof RequestStack) {
            $request = $requestStack->getMasterRequest();
        }

        return self::createContext(
            $application['current_user'],
            $application['jwt'],
            $application['api_key'],
            $application['api_name'],
            $request,
            $application['consumer']
        );
    }

    public static function prepareForLogging(Metadata $metadata): Metadata
    {
        $metadata = $metadata->serialize();

        // Don't store the JWT when logging the metadata in the event store.
        unset($metadata['auth_jwt']);

        // Convert the ApiKey object to a string so it can get JSON-encoded.
        if (isset($metadata['auth_api_key'])) {
            $metadata['auth_api_key'] = (string) $metadata['auth_api_key'];
        }

        return new Metadata($metadata);
    }
}
