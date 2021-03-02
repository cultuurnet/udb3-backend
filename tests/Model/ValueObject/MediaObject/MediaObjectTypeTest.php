<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use PHPUnit\Framework\TestCase;

class MediaObjectTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_exactly_two_values()
    {
        $imageObject = MediaObjectType::imageObject();
        $mediaObject = MediaObjectType::mediaObject();

        $this->assertEquals('imageObject', $imageObject->toString());
        $this->assertEquals('mediaObject', $mediaObject->toString());
    }
}
