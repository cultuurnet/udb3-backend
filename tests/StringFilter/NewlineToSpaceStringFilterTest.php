<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class NewlineToSpaceStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StringFilterInterface
    {
        return new NewlineToSpaceStringFilter();
    }

    /**
     * @test
     */
    public function it_converts_newlines_to_spaces(): void
    {
        $original = "Hello\nworld!\nGoodbye!";
        $expected = 'Hello world! Goodbye!';
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_converts_consecutive_newlines_to_a_single_space(): void
    {
        $original = "Hello\n\nworld!";
        $expected = 'Hello world!';
        $this->assertFilterValue($expected, $original);
    }
}
