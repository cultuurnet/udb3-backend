<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class LabelNameUniqueConstraintServiceTest extends TestCase
{
    private string $name;

    private DomainMessage $created;

    private DomainMessage $copyCreated;

    private LabelNameUniqueConstraintService $uniqueHelper;

    protected function setUp(): void
    {
        $this->name = 'labelName';

        $this->created = $this->createDomainMessage(new Created(
            new UUID('23ad437f-b6f0-4fc4-95c0-0c6faf13050f'),
            $this->name,
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        ));

        $this->copyCreated = $this->createDomainMessage(new CopyCreated(
            new UUID('55dacee0-d7de-4070-920b-c80d5985b687'),
            $this->name,
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PRIVATE(),
            new UUID('01395b0b-001c-4425-9d57-19688d2d27fa')
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
    public function it_requires_unique_for_copy_created(): void
    {
        $this->assertTrue($this->uniqueHelper->hasUniqueConstraint(
            $this->copyCreated
        ));
    }

    /**
     * @test
     */
    public function it_does_not_require_unique_for_made_invisible(): void
    {
        $this->assertFalse($this->uniqueHelper->hasUniqueConstraint(
            $this->createDomainMessage(
                new MadeInvisible(
                    new UUID('f9b74707-5d2d-4dbb-886b-b149786a94c5'),
                    '2dotstwice'
                )
            )
        ));
    }

    /**
     * @test
     */
    public function it_never_allows_update_of_unique_constraint(): void
    {
        $this->assertFalse($this->uniqueHelper->needsUpdateUniqueConstraint(
            $this->copyCreated
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

    /**
     * @test
     */
    public function it_can_get_unique_from_copy_created(): void
    {
        $this->assertEquals(
            $this->name,
            $this->uniqueHelper->getUniqueConstraintValue($this->copyCreated)
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
