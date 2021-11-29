<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class OrganizerCreatedWithUniqueWebsiteTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        OrganizerCreatedWithUniqueWebsite $organizerCreated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $organizerCreated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        OrganizerCreatedWithUniqueWebsite $expectedOrganizerCreated
    ): void {
        $this->assertEquals(
            $expectedOrganizerCreated,
            OrganizerCreatedWithUniqueWebsite::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'organizerCreatedWithUniqueWebsite' => [
                [
                    'organizer_id' => 'organizer_id',
                    'main_language' => 'en',
                    'website' => 'http://www.stuk.be',
                    'title' => 'title',
                ],
                new OrganizerCreatedWithUniqueWebsite(
                    'organizer_id',
                    'en',
                    'http://www.stuk.be',
                    'title'
                ),
            ],
        ];
    }
}
