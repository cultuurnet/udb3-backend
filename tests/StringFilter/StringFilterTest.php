<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

use PHPUnit\Framework\TestCase;

abstract class StringFilterTest extends TestCase
{
    /**
     * Name of the filter class to instantiate.
     *
     * Use the class constant to set this property, eg. StringFilter::class.
     *
     * If you want to pass arguments to the filter's constructor, you should override getFilter() and construct the
     * object yourself instead of setting this property.
     */
    protected string $filterClass;

    protected StringFilterInterface $filter;

    public function setUp(): void
    {
        parent::setUp();
        $this->filter = $this->getFilter();
    }

    /**
     * Returns the filter to be used in all the test methods of the test.
     */
    protected function getFilter(): StringFilterInterface
    {
        return new $this->filterClass();
    }

    /**
     * Uses the $filter property to filter a string.
     */
    protected function filter(string $string): string
    {
        return $this->filter->filter($string);
    }

    /**
     * Asserts that a filtered string is the same as a another string.
     */
    protected function assertFilterValue(string $expected, string $original): void
    {
        $actual = $this->filter($original);
        $this->assertEquals($expected, $actual);
    }
}
