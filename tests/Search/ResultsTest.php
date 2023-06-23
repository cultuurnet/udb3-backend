<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class ResultsTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_instantiated_with_result_items_and_total(): void
    {
        $items = new ItemIdentifiers(
            new ItemIdentifier(
                new Url('http://du.de/event/1'),
                '1',
                ItemType::event()
            ),
            new ItemIdentifier(
                new Url('http://du.de/event/2'),
                '2',
                ItemType::event()
            ),
            new ItemIdentifier(
                new Url('http://du.de/event/3'),
                '3',
                ItemType::event()
            ),
            new ItemIdentifier(
                new Url('http://du.de/event/4'),
                '4',
                ItemType::event()
            )
        );
        $totalItems = 20;

        $results = new Results($items, $totalItems);

        $this->assertEquals($items->toArray(), $results->getItems());
        $this->assertEquals($totalItems, $results->getTotalItems());
    }
}
