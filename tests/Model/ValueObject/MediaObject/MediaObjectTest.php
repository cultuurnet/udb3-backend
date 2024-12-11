<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
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
        $contentUrl = new Url('http://publiq.be/test.png');
        $thumbnailUrl = new Url('http://publiq.be/test.png?w=100&h=100');

        $mediaObject = new MediaObject(
            $id,
            $type,
            $contentUrl,
            $thumbnailUrl
        );

        $this->assertEquals($id, $mediaObject->getId());
        $this->assertEquals($type, $mediaObject->getType());
        $this->assertEquals($contentUrl, $mediaObject->getContentUrl());
        $this->assertEquals($thumbnailUrl, $mediaObject->getThumbnailUrl());
    }
}
