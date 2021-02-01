<?php

namespace CultuurNet\UDB3\StringFilter;

class CombinedStringFilter implements StringFilterInterface
{
    /**
     * @var StringFilterInterface[]
     */
    protected $filters = array();

    /**
     * @param StringFilterInterface $filter
     */
    public function addFilter($filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($string)
    {
        foreach ($this->filters as $filter) {
            $string = $filter->filter($string);
        }

        return $string;
    }
}
