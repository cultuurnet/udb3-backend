<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML;

class TransformingIteratorIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_passes_each_iterated_item_through_a_callback()
    {
        $items = [
            2,
            0,
            10,
            6,
            21,
        ];

        $increaseByOne = function ($i) {
            return $i + 1;
        };

        $iterator = new TransformingIteratorIterator(
            new \ArrayIterator($items),
            $increaseByOne
        );

        $newItems = iterator_to_array($iterator);

        $this->assertEquals(
            [
                3,
                1,
                11,
                7,
                22,
            ],
            $newItems
        );
    }
}
