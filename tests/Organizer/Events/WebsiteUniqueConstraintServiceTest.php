<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\Organizer\WebsiteUniqueConstraintService;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class WebsiteUniqueConstraintServiceTest extends TestCase
{
    /**
     * @var WebsiteUniqueConstraintService
     */
    private $service;

    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var DomainMessage
     */
    private $websiteCreatedEvent;

    /**
     * @var DomainMessage
     */
    private $websiteUpdatedEvent;

    /**
     * @var DomainMessage
     */
    private $unsupportedEvent;

    public function setUp()
    {
        $this->service = new WebsiteUniqueConstraintService(new WebsiteNormalizer());

        $this->organizerId = '2fad63f2-4da2-4c32-ae97-6a581d0e84d2';

        $this->websiteCreatedEvent = DomainMessage::recordNow(
            $this->organizerId,
            0,
            new Metadata([]),
            new OrganizerCreatedWithUniqueWebsite(
                $this->organizerId,
                new Language('en'),
                Url::fromNative('http://cultuurnet.be'),
                new Title('CultuurNet')
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
                new Title('CultuurNet'),
                [],
                [],
                [],
                []
            )
        );
    }

    /**
     * @test
     */
    public function it_supports_organizer_created_with_unique_website_events()
    {
        $this->assertTrue($this->service->hasUniqueConstraint($this->websiteCreatedEvent));
    }

    /**
     * @test
     */
    public function it_supports_website_updated_events()
    {
        $this->assertTrue($this->service->hasUniqueConstraint($this->websiteUpdatedEvent));
    }

    /**
     * @test
     */
    public function it_does_not_support_organizer_created_events()
    {
        $this->assertFalse($this->service->hasUniqueConstraint($this->unsupportedEvent));
    }

    /**
     * @test
     */
    public function it_allows_update_of_unique_constraint_for_website_updated()
    {
        $this->assertTrue(
            $this->service->needsUpdateUniqueConstraint($this->websiteUpdatedEvent)
        );
    }

    /**
     * @test
     */
    public function it_does_not_allow_update_of_unique_constraint_for_website_updated()
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
                new Language('en'),
                Url::fromNative('http://decorridor.be'),
                new Title('CultuurNet')
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
    public function it_throws_an_exception_when_trying_to_get_a_unique_constraint_value_from_unsupported_events()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getUniqueConstraintValue($this->unsupportedEvent);
    }
}
