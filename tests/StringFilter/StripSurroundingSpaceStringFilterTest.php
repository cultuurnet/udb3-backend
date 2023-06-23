<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

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
            file_get_contents(__DIR__ . '/text_without_surrounding_whitespace.txt'),
            file_get_contents(__DIR__ . '/text_surrounded_by_whitespace.txt')
        );
    }
}
