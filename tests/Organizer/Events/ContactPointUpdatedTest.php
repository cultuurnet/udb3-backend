<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class ContactPointUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized(): void
    {
        $contactPointUpdated = new ContactPointUpdated(
            '0460ffbd-1c85-4bad-9a8f-be1f981648e7',
            ['+32 444 56 56 56'],
            ['foo@bar.com'],
            ['http://bar.com']
        );

        $data = $contactPointUpdated->serialize();
        $deserialized = ContactPointUpdated::deserialize($data);

        $this->assertEquals($contactPointUpdated, $deserialized);
    }
}
