<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class ConsecutiveBlockOfTextStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StringFilterInterface
    {
        return new ConsecutiveBlockOfTextStringFilter();
    }

    /**
     * @test
     */
    public function it_formats_string_as_a_consecutive_single_line_of_text(): void
    {
        $this->assertFilterValue(
            file_get_contents(__DIR__ . '/text_consecutive_block.txt'),
            file_get_contents(__DIR__ . '/text_surrounded_by_whitespace.txt')
        );
    }
}
