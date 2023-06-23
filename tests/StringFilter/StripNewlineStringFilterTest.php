<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class StripNewlineStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StringFilterInterface
    {
        return new StripNewlineStringFilter();
    }

    /**
     * @test
     */
    public function it_strips_newlines(): void
    {
        $original = "\nHello\n world!\n Goodbye!\n";
        $expected = 'Hello world! Goodbye!';
        $this->assertFilterValue($expected, $original);
    }
}
