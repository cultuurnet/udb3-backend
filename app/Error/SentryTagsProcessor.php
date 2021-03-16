<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Monolog\Processor\ProcessorInterface;

final class SentryTagsProcessor implements ProcessorInterface
{
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

    public function __construct(?Udb3Token $udb3Token, ?ApiKey $apiKey, ?string $apiName)
    {
        $this->udb3Token = $udb3Token;
        $this->apiKey = $apiKey;
        $this->apiName = $apiName;
    }

    public function __invoke(array $record): array
    {
        // The Sentry Monolog handler has no support for setting user info (yet), so we set it as tags instead for now.
        $record['context']['tags'] = array_merge(
            $this->createApiTags(),
            $this->createUserTags()
        );
        return $record;
    }

    private function createApiTags(): array
    {
        return [
            'api_key' => $this->apiKey ? $this->apiKey->toString() : 'null',
            'api_name' => $this->apiName ?? 'null',
        ];
    }

    private function createUserTags(): array
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
}
