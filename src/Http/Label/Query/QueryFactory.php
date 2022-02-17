<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

class QueryFactory implements QueryFactoryInterface
{
    public const QUERY = 'query';
    public const START = 'start';
    public const LIMIT = 'limit';

    private ?string $userId;

    public function __construct(?string $userId)
    {
        $this->userId = $userId;
    }

    public function createFromRequest(Request $request): Query
    {
        $value = $request->query->get(self::QUERY) !== null
            ? new StringLiteral($request->query->get(self::QUERY)) : new StringLiteral('');

        $userId = $this->userId ? new StringLiteral($this->userId) : null;

        $offset = (int) $request->query->get(self::START);

        $limit = (int) $request->query->get(self::LIMIT);

        return new Query(
            $value,
            $userId,
            $offset,
            $limit
        );
    }
}
