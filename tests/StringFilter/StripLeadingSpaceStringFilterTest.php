<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class StripLeadingSpaceStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StringFilterInterface
    {
        return new StripLeadingSpaceStringFilter();
    }

    /**
     * @test
     */
    public function it_strips_leading_spaces(): void
    {
        $original = "   Hello!   \n       Goodbye!\n\n\n Hello again!  ";
        $expected = "Hello!   \nGoodbye!\n\n\nHello again!  ";
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_strips_leading_tabs(): void
    {
        $original = "\tHello!\t   \n \t      Goodbye!\n\n\n Hello again!\t";
        $expected = "Hello!\t   \nGoodbye!\n\n\nHello again!\t";
        $this->assertFilterValue($expected, $original);
    }
}
