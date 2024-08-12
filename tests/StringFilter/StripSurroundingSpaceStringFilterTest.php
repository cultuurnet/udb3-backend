<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

use CultuurNet\UDB3\SampleFiles;

class StripSurroundingSpaceStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StripSurroundingSpaceStringFilter
    {
        return new StripSurroundingSpaceStringFilter();
    }

    /**
     * @test
     */
    public function it_strips_leading_and_trailing_space_and_tabs(): void
    {
        $this->assertFilterValue(
            SampleFiles::read(__DIR__ . '/text_without_surrounding_whitespace.txt'),
            SampleFiles::read(__DIR__ . '/text_surrounded_by_whitespace.txt')
        );
    }
}
