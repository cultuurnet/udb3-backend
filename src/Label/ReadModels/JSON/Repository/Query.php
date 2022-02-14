<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use ValueObjects\StringLiteral\StringLiteral;

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
