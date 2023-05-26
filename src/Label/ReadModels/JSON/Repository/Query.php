<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

final class Query
{
    private string $value;

    private ?string $userId;

    private ?int $offset;

    private ?int $limit;

    private bool $excludeExcludedLabels;

    private bool $excludeInvalidLabels;

    public function __construct(
        string $value,
        ?string $userId = null,
        ?int $offset = null,
        ?int $limit = null,
        bool $excludeExcludedLabels = false,
        bool $excludeInvalidLabels = false
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
        $this->excludeExcludedLabels = $excludeExcludedLabels;
        $this->excludeInvalidLabels = $excludeInvalidLabels;
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

    public function isExcludedExcluded(): bool
    {
        return $this->excludeExcludedLabels;
    }

    public function isInvalidExcluded(): bool
    {
        return $this->excludeInvalidLabels;
    }
}
