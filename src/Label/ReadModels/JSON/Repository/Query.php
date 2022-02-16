<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\StringLiteral;

final class Query
{
    private StringLiteral $value;

    private ?StringLiteral $userId;

    private ?int $offset;

    private ?int $limit;

    public function __construct(
        StringLiteral $value,
        StringLiteral $userId = null,
        ?int $offset = null,
        ?int $limit = null
    ) {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset should be zero or higher');
        }

        if ($limit < 0) {
            throw new \InvalidArgumentException('Limit should be zero or higher');
        }

        $this->value = $value;
        $this->userId = $userId;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function getValue(): StringLiteral
    {
        return $this->value;
    }

    public function getUserId(): ?StringLiteral
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
