<?php

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class LabelNameUniqueConstraintServiceTest extends TestCase
{
    /**
     * @var LabelName
     */
    private $name;

    /**
     * @var DomainMessage
     */
    private $created;

    /**
     * @var DomainMessage
     */
    private $copyCreated;

    /**
     * @var LabelNameUniqueConstraintService
     */
    private $uniqueHelper;

    protected function setUp()
    {
        $this->name = new LabelName('labelName');

        $this->created = $this->createDomainMessage(new Created(
            new UUID(),
            $this->name,
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        ));

        $this->copyCreated = $this->createDomainMessage(new CopyCreated(
            new UUID(),
            $this->name,
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PRIVATE(),
            new UUID()
        ));

        $this->uniqueHelper = new LabelNameUniqueConstraintService();
    }

    /**
     * @test
     */
    public function it_requires_unique_for_created()
    {
        $this->assertTrue($this->uniqueHelper->hasUniqueConstraint($this->created));
    }

    /**
     * @test
     */
    public function it_requires_unique_for_copy_created()
    {
        $this->assertTrue($this->uniqueHelper->hasUniqueConstraint(
            $this->copyCreated
        ));
    }

    /**
     * @test
     */
    public function it_does_not_require_unique_for_made_invisible()
    {
        $this->assertFalse($this->uniqueHelper->hasUniqueConstraint(
            $this->createDomainMessage(
                new MadeInvisible(
                    new UUID(),
                    new LabelName('2dotstwice')
                )
            )
        ));
    }

    /**
     * @test
     */
    public function it_never_allows_update_of_unique_constraint()
    {
        $this->assertFalse($this->uniqueHelper->needsUpdateUniqueConstraint(
            $this->copyCreated
        ));
    }

    /**
     * @test
     */
    public function it_can_get_unique_from_created()
    {
        $this->assertEquals(
            $this->name,
            $this->uniqueHelper->getUniqueConstraintValue($this->created)
        );
    }

    /**
     * @test
     */
    public function it_can_get_unique_from_copy_created()
    {
        $this->assertEquals(
            $this->name,
            $this->uniqueHelper->getUniqueConstraintValue($this->copyCreated)
        );
    }

    /**
     * @param AbstractEvent $event
     * @return DomainMessage
     */
    private function createDomainMessage(AbstractEvent $event)
    {
        return new DomainMessage(
            $event->getUuid(),
            0,
            new Metadata(),
            $event,
            BroadwayDateTime::now()
        );
    }
}
