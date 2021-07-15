<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\AbstractToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\AccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0ClientAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0UserAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV1Token;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV2Token;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Sentry\State\Scope;
use function Sentry\withScope;

/**
 * @see https://github.com/getsentry/sentry-php/blob/master/UPGRADE-3.0.md
 */
final class SentryHandlerScopeDecorator implements HandlerInterface
{
    /**
     * @var HandlerInterface
     */
    private $decoratedHandler;

    /**
     * @var AbstractToken|null
     */
    private $jwt;

    /**
     * @var ApiKey|null
     */
    private $apiKey;

    /**
     * @var string|null
     */
    private $apiName;

    public function __construct(
        HandlerInterface $decoratedHandler,
        ?AbstractToken $jwt,
        ?ApiKey $apiKey,
        ?string $apiName
    ) {
        $this->decoratedHandler = $decoratedHandler;
        $this->jwt = $jwt;
        $this->apiKey = $apiKey;
        $this->apiName = $apiName;
    }

    public function handle(array $record): bool
    {
        $result = false;

        withScope(function (Scope $scope) use ($record, &$result): void {
            $scope->setTags($this->createApiTags());
            $scope->setUser($this->createUserInfo());

            $result = $this->decoratedHandler->handle($record);
        });

        return $result;
    }

    private function createApiTags(): array
    {
        $clientId = $this->jwt instanceof AccessToken ? $this->jwt->getClientId() : null;
        return [
            'api_client_id' => $clientId ?? 'null',
            'api_key' => $this->apiKey ? $this->apiKey->toString() : 'null',
            'api_name' => $this->apiName ?? 'null',
        ];
    }

    private function createUserInfo(): array
    {
        if (!$this->jwt) {
            return ['id' => 'anonymous'];
        }

        $tokenTypes = [
            JwtProviderV1Token::class => 'v1_jwt_provider_token',
            JwtProviderV2Token::class => 'v2_jwt_provider_token',
            Auth0UserAccessToken::class => 'v2_user_access_token',
            Auth0ClientAccessToken::class => 'v2_client_access_token',
        ];

        $tokenClass = get_class($this->jwt);

        return [
            'id' => $this->jwt->getUserId(),
            'token_type' => $tokenTypes[$tokenClass] ?? 'null',
        ];
    }

    public function handleBatch(array $records): void
    {
        $this->decoratedHandler->handleBatch($records);
    }

    public function isHandling(array $record): bool
    {
        return $this->decoratedHandler->isHandling($record);
    }

    public function pushProcessor($callback): self
    {
        $this->decoratedHandler->pushProcessor($callback);
        return $this;
    }

    public function popProcessor(): callable
    {
        return $this->decoratedHandler->popProcessor();
    }

    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->decoratedHandler->setFormatter($formatter);
        return $this;
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->decoratedHandler->getFormatter();
    }
}
