<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class TitleUpdatedTest extends TestCase
{
    private TitleUpdated $titleUpdated;

    private array $titleUpdatedAsArray;

    protected function setUp(): void
    {
        $organizerId = '3ad6c135-9b2d-4360-8886-3a58aaf66039';

        $title = 'Het Depot';

        $this->titleUpdated = new TitleUpdated(
            $organizerId,
            $title
        );

        $this->titleUpdatedAsArray = [
            'organizer_id' =>  $organizerId,
            'title' => $title,
        ];
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array(): void
    {
        $this->assertEquals(
            $this->titleUpdatedAsArray,
            $this->titleUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array(): void
    {
        $this->assertEquals(
            TitleUpdated::deserialize($this->titleUpdatedAsArray),
            $this->titleUpdated
        );
    }
}
