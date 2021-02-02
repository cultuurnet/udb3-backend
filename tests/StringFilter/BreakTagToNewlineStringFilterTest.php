<?php

namespace CultuurNet\UDB3\StringFilter;

class BreakTagToNewlineStringFilterTest extends StringFilterTest
{
    /**
     * @return BreakTagToNewlineStringFilter
     */
    protected function getFilter()
    {
        return new BreakTagToNewlineStringFilter();
    }

    /**
     * @test
     */
    public function it_converts_break_tags_to_newlines()
    {
        $original = "Hello<br>world!<br/>Goodbye!<br />Nice to have known you!";
        $expected = "Hello\nworld!\nGoodbye!\nNice to have known you!";
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_converts_consecutive_break_tags_to_consecutive_newlines()
    {
        $original = "Hello<br /><br />world!";
        $expected = "Hello\n\nworld!";
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
