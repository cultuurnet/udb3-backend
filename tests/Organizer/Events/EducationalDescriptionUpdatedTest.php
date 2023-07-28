<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class EducationalDescriptionUpdatedTest extends TestCase
{
    private EducationalDescriptionUpdated $educationalDescriptionUpdated;

    protected function setUp(): void
    {
        $this->educationalDescriptionUpdated = new EducationalDescriptionUpdated(
            '914dfde8-940a-4b8f-8316-029b1a0248aa',
            'This is the educational description of the organizer',
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
            $this->educationalDescriptionUpdated->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_description(): void
    {
        $this->assertEquals(
            'This is the educational description of the organizer',
            $this->educationalDescriptionUpdated->getEducationalDescription()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals('en', $this->educationalDescriptionUpdated->getLanguage());
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $this->assertEquals(
            [
                'organizer_id' => '914dfde8-940a-4b8f-8316-029b1a0248aa',
                'educational_description' => 'This is the educational description of the organizer',
                'language' => 'en',
            ],
            $this->educationalDescriptionUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $this->assertEquals(
            $this->educationalDescriptionUpdated,
            EducationalDescriptionUpdated::deserialize(
                [
                    'organizer_id' => '914dfde8-940a-4b8f-8316-029b1a0248aa',
                    'educational_description' => 'This is the educational description of the organizer',
                    'language' => 'en',
                ]
            )
        );
    }
}
