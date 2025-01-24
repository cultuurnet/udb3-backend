<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class MediaObjectTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_the_injected_properties(): void
    {
        $id = new Uuid('2a04345a-5e3e-4a23-a513-ce8197a10af6');
        $type = MediaObjectType::imageObject();

        $mediaObject = new MediaObject(
            $id,
            $type
        );

        $this->assertEquals($id, $mediaObject->getId());
        $this->assertEquals($type, $mediaObject->getType());
    }
}
