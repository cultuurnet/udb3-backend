<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use PHPUnit\Framework\TestCase;

class TransformingIteratorIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_passes_each_iterated_item_through_a_callback(): void
    {
        $items = [
            2,
            0,
            10,
            6,
            21,
        ];

        $increaseByOne = function ($i) use (&$increaseByOne) {
            if (is_array($i)) {
                return array_map($increaseByOne, $i);
            } else {
                return $i + 1;
            }
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
                [
                    4,
                    8,
                ],
                22,
            ],
            $newItems
        );
    }
}
