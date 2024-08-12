<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

use CultuurNet\UDB3\SampleFiles;

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
            SampleFiles::read(__DIR__ . '/text_consecutive_block.txt'),
            SampleFiles::read(__DIR__ . '/text_surrounded_by_whitespace.txt')
        );
    }
}
