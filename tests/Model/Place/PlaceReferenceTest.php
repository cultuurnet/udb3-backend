<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class PlaceReferenceTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_using_a_place_id(): void
    {
        $id = new Uuid('38d78529-29b8-4635-a26e-51bbb2eba535');
        $reference = PlaceReference::createWithPlaceId($id);

        $this->assertEquals($id, $reference->getPlaceId());
    }
}
