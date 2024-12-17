<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class LabelNameUniqueConstraintServiceTest extends TestCase
{
    private string $name;

    private DomainMessage $created;

    private LabelNameUniqueConstraintService $uniqueHelper;

    protected function setUp(): void
    {
        $this->name = 'labelName';

        $this->created = $this->createDomainMessage(new Created(
            new Uuid('23ad437f-b6f0-4fc4-95c0-0c6faf13050f'),
            $this->name,
            Visibility::visible(),
            Privacy::private()
        ));

        $this->uniqueHelper = new LabelNameUniqueConstraintService();
    }

    /**
     * @test
     */
    public function it_requires_unique_for_created(): void
    {
        $this->assertTrue($this->uniqueHelper->hasUniqueConstraint($this->created));
    }

    /**
     * @test
     */
    public function it_does_not_require_unique_for_made_invisible(): void
    {
        $this->assertFalse($this->uniqueHelper->hasUniqueConstraint(
            $this->createDomainMessage(
                new MadeInvisible(
                    new Uuid('f9b74707-5d2d-4dbb-886b-b149786a94c5'),
                    '2dotstwice'
                )
            )
        ));
    }

    /**
     * @test
     */
    public function it_can_get_unique_from_created(): void
    {
        $this->assertEquals(
            $this->name,
            $this->uniqueHelper->getUniqueConstraintValue($this->created)
        );
    }

    private function createDomainMessage(AbstractEvent $event): DomainMessage
    {
        return new DomainMessage(
            $event->getUuid()->toString(),
            0,
            new Metadata(),
            $event,
            BroadwayDateTime::now()
        );
    }
}
