<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class LabelAddedTest extends TestCase
{
    private LabelAdded $labelAdded;

    protected function setUp(): void
    {
        $this->labelAdded = new LabelAdded('organizerId', 'foo', false);
    }

    /**
     * @test
     */
    public function it_derives_from_abstract_label_event(): void
    {
        $this->assertInstanceOf(AbstractLabelEvent::class, $this->labelAdded);
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $labelAddesAsArray = [
            'organizer_id' => 'organizerId',
            'label' => 'foo',
            'visibility' => false,
        ];

        $this->assertEquals(
            $this->labelAdded,
            LabelAdded::deserialize($labelAddesAsArray)
        );
    }
}
