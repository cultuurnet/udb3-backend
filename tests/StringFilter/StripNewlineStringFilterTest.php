<?php

namespace CultuurNet\UDB3\StringFilter;

class StripNewlineStringFilterTest extends StringFilterTest
{
    /**
     * @return StripNewlineStringFilter
     */
    protected function getFilter()
    {
        return new StripNewlineStringFilter();
    }

    /**
     * @test
     */
    public function it_strips_newlines()
    {
        $original = "\nHello\n world!\n Goodbye!\n";
        $expected = "Hello world! Goodbye!";
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
