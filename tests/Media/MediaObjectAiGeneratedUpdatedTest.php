<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Events\MediaObjectAiGeneratedUpdated;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

final class MediaObjectAiGeneratedUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized(): void
    {
        $event = new MediaObjectAiGeneratedUpdated(new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'), true);

        $serialized = [
            'media_object_id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
            'is_ai_generated' => true,
        ];

        $this->assertEquals($serialized, $event->serialize());
        $this->assertEquals($event, MediaObjectAiGeneratedUpdated::deserialize($serialized));
    }
}
