<?php

namespace CultuurNet\UDB3\StringFilter;

class StripSurroundingSpaceStringFilterTest extends StringFilterTest
{
    /**
     * @return StripSurroundingSpaceStringFilter
     */
    protected function getFilter()
    {
        return new StripSurroundingSpaceStringFilter();
    }

    /**
     * @test
     */
    public function it_only_filters_strings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->filter->filter(12345);
    }

    /**
     * @test
     */
    public function it_strips_leading_and_trailing_space_and_tabs()
    {
        $this->assertFilterValue(
            file_get_contents(__DIR__ . '/text_without_surrounding_whitespace.txt'),
            file_get_contents(__DIR__ . '/text_surrounded_by_whitespace.txt')
        );
    }
}
