<?php

namespace CultuurNet\UDB3\StringFilter;

class StripTrailingSpaceStringFilterTest extends StringFilterTest
{
    /**
     * @return StripTrailingSpaceStringFilter
     */
    protected function getFilter()
    {
        return new StripTrailingSpaceStringFilter();
    }

    /**
     * @test
     */
    public function it_strips_trailing_spaces()
    {
        $original = "    Hello!   \n Goodbye!  \n\n\nHello again!";
        $expected = "    Hello!\n Goodbye!\n\n\nHello again!";
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_strips_trailing_tabs()
    {
        $original = "    \tHello!\t   \n Goodbye!  \n\n\nHello again!";
        $expected = "    \tHello!\n Goodbye!\n\n\nHello again!";
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_only_filters_strings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->filter->filter(12345);
    }
}
