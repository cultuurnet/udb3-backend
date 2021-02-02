<?php

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
     * @param array $expectedSerializedValue
     * @param OrganizerCreatedWithUniqueWebsite $organizerCreated
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        OrganizerCreatedWithUniqueWebsite $organizerCreated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $organizerCreated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param OrganizerCreatedWithUniqueWebsite $expectedOrganizerCreated
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        OrganizerCreatedWithUniqueWebsite $expectedOrganizerCreated
    ) {
        $this->assertEquals(
            $expectedOrganizerCreated,
            OrganizerCreatedWithUniqueWebsite::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
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
                    new Language('en'),
                    Url::fromNative('http://www.stuk.be'),
                    new Title('title')
                ),
            ],
        ];
    }
}
