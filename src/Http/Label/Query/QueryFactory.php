<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class QueryFactory implements QueryFactoryInterface
{
    public const QUERY = 'query';
    public const START = 'start';
    public const LIMIT = 'limit';

    /**
     * @var ?string
     */
    private $userId;

    public function __construct(?string $userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return Query
     */
    public function createFromRequest(Request $request)
    {
        $value = $request->query->get(self::QUERY) !== null
            ? new StringLiteral($request->query->get(self::QUERY)) : new StringLiteral('');

        $userId = $this->userId ? new StringLiteral($this->userId) : null;

        $offset = $request->query->get(self::START, null) !== null
            ? new Natural($request->query->get(self::START)) : null;

        $limit = $request->query->get(self::LIMIT, null) !== null
            ? new Natural($request->query->get(self::LIMIT)) : null;

        return new Query(
            $value,
            $userId,
            $offset,
            $limit
        );
    }
}
