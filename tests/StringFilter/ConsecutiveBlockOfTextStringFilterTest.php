<?php

namespace CultuurNet\UDB3\StringFilter;

class ConsecutiveBlockOfTextStringFilterTest extends StringFilterTest
{
    /**
     * @return ConsecutiveBlockOfTextStringFilter
     */
    protected function getFilter()
    {
        return new ConsecutiveBlockOfTextStringFilter();
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
    public function it_formats_string_as_a_consecutive_single_line_of_text()
    {
        $this->assertFilterValue(
            file_get_contents(__DIR__ . '/text_consecutive_block.txt'),
            file_get_contents(__DIR__ . '/text_surrounded_by_whitespace.txt')
        );
    }
}
