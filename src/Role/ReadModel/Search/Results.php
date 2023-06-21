<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search;

class Results
{
    private int $itemsPerPage;

    private array $member;

    private int $totalItems;

    public function __construct(int $itemsPerPage, array $member, int $totalItems)
    {
        $this->itemsPerPage = $itemsPerPage;
        $this->member = $member;
        $this->totalItems = $totalItems;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getMember(): array
    {
        return $this->member;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }
}
