<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventListener;

use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use PHPUnit\Framework\TestCase;

class ClassNameEventSpecificationTest extends TestCase
{
    private LabelAdded $labelAdded;

    protected function setUp(): void
    {
        $this->labelAdded = new LabelAdded(
            '26e36905-64d0-4cac-ba41-6d6dcd997ca0',
            'UiTPAS'
        );
    }

    /**
     * @test
     */
    public function it_returns_true_when_class_name_matches(): void
    {
        $classNameEventFilter = new ClassNameEventSpecification(
            LabelAdded::class,
            LabelRemoved::class
        );

        $this->assertTrue($classNameEventFilter->matches($this->labelAdded));
    }

    /**
     * @test
     */
    public function it_returns_false_when_class_name_does_not_match(): void
    {
        $classNameEventFilter = new ClassNameEventSpecification(LabelRemoved::class);

        $this->assertFalse($classNameEventFilter->matches($this->labelAdded));
    }
}
