<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

final class Query
{
    private string $value;

    private ?string $userId;

    private ?int $offset;

    private ?int $limit;

    private bool $suggestion;

    public function __construct(
        string $value,
        ?string $userId = null,
        ?int $offset = null,
        ?int $limit = null,
        bool $suggestion = false
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
        $this->suggestion = $suggestion;
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

    public function isSuggestion(): bool
    {
        return $this->suggestion;
    }
}
