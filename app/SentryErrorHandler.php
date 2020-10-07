<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Throwable;

class SentryErrorHandler
{
    /** @var HubInterface */
    private $sentryHub;

    /** @var Udb3Token|null */
    private $udb3Token;

    /** @var ApiKey|null */
    private $apiKey;

    /** @var string|null */
    private $apiName;

    public function __construct(HubInterface $sentryHub, ?Udb3Token $udb3Token, ?ApiKey $apiKey, ?string $apiName)
    {
        $this->sentryHub = $sentryHub;
        $this->udb3Token = $udb3Token;
        $this->apiKey = $apiKey;
        $this->apiName = $apiName;
    }

    public function handle(Throwable $throwable): void
    {
        $this->sentryHub->configureScope(function (Scope $scope) {
            $scope->setUser($this->createUser($this->udb3Token));
            $scope->setTags($this->createTags($this->apiKey, $this->apiName));
        });

        $this->sentryHub->captureException($throwable);
        throw $throwable;
    }

    private function createUser(?Udb3Token $udb3Token): array
    {
        if ($udb3Token === null) {
            return ['id' => 'anonymous'];
        }

        return [
            'id' => $udb3Token->id(),
            'uid' => $udb3Token->jwtToken()->getClaim('uid', 'null'),
            'uitidv1id' => $udb3Token->jwtToken()->getClaim('https://publiq.be/uitidv1id', 'null'),
            'sub' => $udb3Token->jwtToken()->getClaim('sub', 'null'),
        ];
    }

    private function createTags(?ApiKey $apiKey, ?string $apiName): array
    {
        return [
            'api_key' => $apiKey ? $apiKey->toNative() : 'null',
            'api_name' => $apiName ?? 'null',
        ];
    }
}
