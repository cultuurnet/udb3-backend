<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use Psr\Http\Message\ServerRequestInterface;

class QueryFactory implements QueryFactoryInterface
{
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

        $offset = (int) $queryParameters->get(self::START);

        $limit = (int) $queryParameters->get(self::LIMIT);

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
