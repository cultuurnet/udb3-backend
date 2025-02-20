<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use Psr\Http\Message\ServerRequestInterface;

class QueryFactory implements QueryFactoryInterface
{
    public const MAX_LIMIT = 30;
    public const QUERY = 'query';
    public const START = 'start';
    public const LIMIT = 'limit';
    public const SUGGESTION = 'suggestion';

    private ?string $userId;

    public function __construct(?string $userId)
    {
        $this->userId = $userId;
    }

    public function createFromRequest(ServerRequestInterface $request): Query
    {
        $queryParameters = new QueryParameters($request);

        $value = $queryParameters->get(self::QUERY) !== null
            ? (string) $queryParameters->get(self::QUERY) : '';

        $userId = $this->userId ?: null;

        $offset = $queryParameters->getAsInt(self::START, 0);

        $limit = min(
            $queryParameters->getAsInt(self::LIMIT, self::MAX_LIMIT),
            self::MAX_LIMIT
        );

        $suggestion = filter_var($queryParameters->get(self::SUGGESTION), FILTER_VALIDATE_BOOLEAN);

        return new Query(
            $value,
            $userId,
            $offset,
            $limit,
            $suggestion
        );
    }
}
