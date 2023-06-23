<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class StripTrailingSpaceStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StripTrailingSpaceStringFilter
    {
        return new StripTrailingSpaceStringFilter();
    }

    /**
     * @test
     */
    public function it_strips_trailing_spaces(): void
    {
        $original = "    Hello!   \n Goodbye!  \n\n\nHello again!";
        $expected = "    Hello!\n Goodbye!\n\n\nHello again!";
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_strips_trailing_tabs(): void
    {
        $original = "    \tHello!\t   \n Goodbye!  \n\n\nHello again!";
        $expected = "    \tHello!\n Goodbye!\n\n\nHello again!";
        $this->assertFilterValue($expected, $original);
    }
}
