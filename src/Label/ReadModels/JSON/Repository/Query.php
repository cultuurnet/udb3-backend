<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

final class Query
{
    private string $value;

    private ?string $userId;

    private ?int $offset;

    private ?int $limit;

    public function __construct(
        string $value,
        ?string $userId = null,
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

    public function getValue(): string
    {
        return $this->value;
    }

    public function getUserId(): ?string
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
