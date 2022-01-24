<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class ItemIdentifierTest extends TestCase
{
    private ItemIdentifier $itemIdentifier;

    protected function setUp(): void
    {
        $this->itemIdentifier = new ItemIdentifier(
            new Url('https://io.uitdatabank.dev/organizers/8ebb29b4-e1b9-44f1-b8f0-0f170903720b'),
            '8ebb29b4-e1b9-44f1-b8f0-0f170903720b',
            ItemType::organizer()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_url(): void
    {
        $this->assertEquals(
            new Url('https://io.uitdatabank.dev/organizers/8ebb29b4-e1b9-44f1-b8f0-0f170903720b'),
            $this->itemIdentifier->getUrl()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(
            '8ebb29b4-e1b9-44f1-b8f0-0f170903720b',
            $this->itemIdentifier->getId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_type(): void
    {
        $this->assertEquals(
            ItemType::organizer(),
            $this->itemIdentifier->getItemType()
        );
    }
}
