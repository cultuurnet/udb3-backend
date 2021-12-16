<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class DescriptionUpdatedTest extends TestCase
{
    private DescriptionUpdated $descriptionUpdated;

    protected function setUp(): void
    {
        $this->descriptionUpdated = new DescriptionUpdated(
            '914dfde8-940a-4b8f-8316-029b1a0248aa',
            'This is the description of the organizer',
            'en'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            '914dfde8-940a-4b8f-8316-029b1a0248aa',
            $this->descriptionUpdated->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_description(): void
    {
        $this->assertEquals(
            'This is the description of the organizer',
            $this->descriptionUpdated->getDescription()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals('en', $this->descriptionUpdated->getLanguage());
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $this->assertEquals(
            [
                'organizer_id' => '914dfde8-940a-4b8f-8316-029b1a0248aa',
                'description' => 'This is the description of the organizer',
                'language' => 'en',
            ],
            $this->descriptionUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $this->assertEquals(
            $this->descriptionUpdated,
            DescriptionUpdated::deserialize(
                [
                    'organizer_id' => '914dfde8-940a-4b8f-8316-029b1a0248aa',
                    'description' => 'This is the description of the organizer',
                    'language' => 'en',
                ]
            )
        );
    }
}
