<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;

class TitleUpdatedTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var Title
     */
    private $title;

    /**
     * @var TitleUpdated
     */
    private $titleUpdated;

    /**
     * @var array
     */
    private $titleUpdatedAsArray;

    protected function setUp()
    {
        $this->organizerId = '3ad6c135-9b2d-4360-8886-3a58aaf66039';

        $this->title = new Title('Het Depot');

        $this->titleUpdated = new TitleUpdated(
            $this->organizerId,
            $this->title
        );

        $this->titleUpdatedAsArray = [
            'organizer_id' =>  $this->organizerId,
            'title' => $this->title->toNative(),
        ];
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array()
    {
        $this->assertEquals(
            $this->titleUpdatedAsArray,
            $this->titleUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array()
    {
        $this->assertEquals(
            TitleUpdated::deserialize($this->titleUpdatedAsArray),
            $this->titleUpdated
        );
    }
}
