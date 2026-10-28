<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Events\MediaObjectRenamed;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class MediaObjectRenamedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized(): void
    {
        $mediaObjectRenamed = new MediaObjectRenamed(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new Url('http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.ai.png')
        );

        $serialized = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'source_location' => 'http://foo.be/de305d54-75b4-431b-adb2-eb6b9e546014.ai.png',
        ];

        $this->assertEquals($serialized, $mediaObjectRenamed->serialize());
        $this->assertEquals($mediaObjectRenamed, MediaObjectRenamed::deserialize($serialized));
    }
}
