<?php

namespace CultuurNet\UDB3\StringFilter;

class NewlineToSpaceStringFilterTest extends StringFilterTest
{
    /**
     * @return NewlineToSpaceStringFilter
     */
    protected function getFilter()
    {
        return new NewlineToSpaceStringFilter();
    }

    /**
     * @test
     */
    public function it_converts_newlines_to_spaces()
    {
        $original = "Hello\nworld!\nGoodbye!";
        $expected = "Hello world! Goodbye!";
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_converts_consecutive_newlines_to_a_single_space()
    {
        $original = "Hello\n\nworld!";
        $expected = "Hello world!";
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
