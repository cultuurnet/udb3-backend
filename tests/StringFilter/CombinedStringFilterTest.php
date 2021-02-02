<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\StringFilter;

use PHPUnit\Framework\TestCase;

class CombinedStringFilterTest extends TestCase
{
    /**
     * @test
     */
    public function test_it_calls_added_string_filters()
    {
        $combinedFilter = new CombinedStringFilter();

        $appendBarFilter = $this->createMock(StringFilterInterface::class);
        $prependFooFilter = $this->createMock(StringFilterInterface::class);

        $combinedFilter->addFilter($appendBarFilter);
        $combinedFilter->addFilter($prependFooFilter);
        $combinedFilter->addFilter($prependFooFilter);

        $appendBarFilter
            ->expects($this->once())
            ->method('filter')
            ->with('this')
            ->willReturnCallback(
                function ($value) {
                    return $value . 'bar';
                }
            );


        $prependFooFilter
            ->expects($this->atLeastOnce())
            ->method('filter')
            ->withConsecutive(
                ['thisbar'],
                ['foothisbar']
            )
            ->willReturnCallback(
                function ($value) {
                    return 'foo' . $value;
                }
            );

        $filteredValue = $combinedFilter->filter('this');

        $this->assertEquals('foofoothisbar', $filteredValue);
    }
}
