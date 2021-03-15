<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Psr\Log\LoggerInterface;
use Throwable;

final class ErrorLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
        LoggerInterface $logger,
        ?Udb3Token $udb3Token,
        ?ApiKey $apiKey,
        ?string $apiName
    ) {
        $this->logger = $logger;
        $this->udb3Token = $udb3Token;
        $this->apiKey = $apiKey;
        $this->apiName = $apiName;
    }

    public function log(Throwable $throwable): void
    {
        $this->logger->error(
            $throwable->getMessage(),
            [
                'exception' => $throwable,
                'tags' => array_merge(
                    [
                        'api_key' => $this->apiKey ? $this->apiKey->toString() : 'null',
                        'api_name' => $this->apiName ?? 'null',
                    ],
                    $this->createUserTags()
                ),
            ]
        );
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
