<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\Organizer\WebsiteUniqueConstraintService;
use PHPUnit\Framework\TestCase;

class WebsiteUniqueConstraintServiceTest extends TestCase
{
    private WebsiteUniqueConstraintService $service;

    private string $organizerId;

    private DomainMessage $websiteCreatedEvent;

    private DomainMessage $websiteUpdatedEvent;

    private DomainMessage $unsupportedEvent;

    public function setUp(): void
    {
        $this->service = new WebsiteUniqueConstraintService(new WebsiteNormalizer());

        $this->organizerId = '2fad63f2-4da2-4c32-ae97-6a581d0e84d2';

        $this->websiteCreatedEvent = DomainMessage::recordNow(
            $this->organizerId,
            0,
            new Metadata([]),
            new OrganizerCreatedWithUniqueWebsite(
                $this->organizerId,
                'en',
                'http://cultuurnet.be',
                'CultuurNet'
            )
        );

        $this->websiteUpdatedEvent = DomainMessage::recordNow(
            $this->organizerId,
            0,
            new Metadata([]),
            new WebsiteUpdated(
                $this->organizerId,
                'http://cultuurnet.be'
            )
        );

        $this->unsupportedEvent = DomainMessage::recordNow(
            $this->organizerId,
            0,
            new Metadata([]),
            new OrganizerCreated(
                $this->organizerId,
                'CultuurNet',
                [],
                [],
                []
            )
        );
    }

    /**
     * @test
     */
    public function it_supports_organizer_created_with_unique_website_events(): void
    {
        $this->assertTrue($this->service->hasUniqueConstraint($this->websiteCreatedEvent));
    }

    /**
     * @test
     */
    public function it_supports_website_updated_events(): void
    {
        $this->assertTrue($this->service->hasUniqueConstraint($this->websiteUpdatedEvent));
    }

    /**
     * @test
     */
    public function it_does_not_support_organizer_created_events(): void
    {
        $this->assertFalse($this->service->hasUniqueConstraint($this->unsupportedEvent));
    }

    /**
     * @test
     */
    public function it_allows_update_of_unique_constraint_for_website_updated(): void
    {
        $this->assertTrue(
            $this->service->needsUpdateUniqueConstraint($this->websiteUpdatedEvent)
        );
    }

    /**
     * @test
     */
    public function it_does_not_allow_update_of_unique_constraint_for_website_updated(): void
    {
        $this->assertFalse(
            $this->service->needsUpdateUniqueConstraint($this->websiteCreatedEvent)
        );
    }

    /**
     * @test
     */
    public function it_returns_the_unique_constraint_value_from_supported_events(): void
    {
        $websiteCreatedEvent = DomainMessage::recordNow(
            $this->organizerId,
            0,
            new Metadata([]),
            new OrganizerCreatedWithUniqueWebsite(
                $this->organizerId,
                'en',
                'http://decorridor.be',
                'CultuurNet'
            )
        );

        $websiteUpdatedEvent = DomainMessage::recordNow(
            $this->organizerId,
            0,
            new Metadata([]),
            new WebsiteUpdated(
                $this->organizerId,
                'http://decorridor.be'
            )
        );

        $this->assertEquals(
            'decorridor.be',
            $this->service->getUniqueConstraintValue($websiteCreatedEvent)
        );

        $this->assertEquals(
            'decorridor.be',
            $this->service->getUniqueConstraintValue($websiteUpdatedEvent)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_trying_to_get_a_unique_constraint_value_from_unsupported_events(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getUniqueConstraintValue($this->unsupportedEvent);
    }
}
