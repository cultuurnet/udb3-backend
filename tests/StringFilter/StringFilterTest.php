<?php

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
     *
     * @var string
     */
    protected $filterClass;

    /**
     * Filter object.
     * @var StringFilterInterface
     */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->filter = $this->getFilter();
    }

    /**
     * Returns the filter to be used in all the test methods of the test.
     * @return StringFilterInterface
     */
    protected function getFilter()
    {
        return new $this->filterClass();
    }

    /**
     * Uses the $filter property to filter a string.
     *
     * @param string $string
     *   String to filter.
     *
     * @return string
     *   Filtered string.
     */
    protected function filter($string)
    {
        return $this->filter->filter($string);
    }

    /**
     * Asserts that a filtered string is the same as a another string.
     *
     * @param string $expected
     *   Expected string value after filtering.
     * @param string $original
     *   String to filter and compare afterwards.
     */
    protected function assertFilterValue($expected, $original)
    {
        $actual = $this->filter($original);
        $this->assertEquals($expected, $actual);
    }
}
