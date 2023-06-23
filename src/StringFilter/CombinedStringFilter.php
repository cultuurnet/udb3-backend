<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class CombinedStringFilter implements StringFilterInterface
{
    /**
     * @var StringFilterInterface[]
     */
    protected array $filters = [];

    public function addFilter(StringFilterInterface $filter): void
    {
        $this->filters[] = $filter;
    }

    public function filter(string $string): string
    {
        foreach ($this->filters as $filter) {
            $string = $filter->filter($string);
        }

        return $string;
    }
}
