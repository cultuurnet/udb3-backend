<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use ValueObjects\StringLiteral\StringLiteral;

class Query
{
    /**
     * @var StringLiteral
     */
    private $value;

    /**
     * @var StringLiteral
     */
    private $userId;

    private ?int $offset;

    private ?int $limit;

    /**
     * Query constructor.
     */
    public function __construct(
        StringLiteral $value,
        StringLiteral $userId = null,
        ?int $offset = null,
        ?int $limit = null
    ) {
        $this->value = $value;
        $this->userId = $userId;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * @return StringLiteral
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return StringLiteral|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
