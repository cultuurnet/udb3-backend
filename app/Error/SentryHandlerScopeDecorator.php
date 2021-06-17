<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Udb3Token;
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
     * @var Udb3Token|null
     */
    private $udb3Token;

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
        ?Udb3Token $udb3Token,
        ?ApiKey $apiKey,
        ?string $apiName
    ) {
        $this->decoratedHandler = $decoratedHandler;
        $this->udb3Token = $udb3Token;
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
        return [
            'api_key' => $this->apiKey ? $this->apiKey->toString() : 'null',
            'api_name' => $this->apiName ?? 'null',
        ];
    }

    private function createUserInfo(): array
    {
        if (!$this->udb3Token) {
            return ['id' => 'anonymous'];
        }

        return [
            'id' => $this->udb3Token->id(),
            'uid' => $this->udb3Token->jwtToken()->getClaim('uid', 'null'),
            'uitidv1id' => $this->udb3Token->jwtToken()->getClaim('https://publiq.be/uitidv1id', 'null'),
            'sub' => $this->udb3Token->jwtToken()->getClaim('sub', 'null'),
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
