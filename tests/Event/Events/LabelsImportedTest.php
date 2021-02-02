<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use PHPUnit\Framework\TestCase;

class LabelsImportedTest extends TestCase
{
    /**
     * @var LabelsImported
     */
    private $labelsImported;

    /**
     * @var array
     */
    private $labelsImportedAsArray;

    protected function setUp()
    {
        $this->labelsImported = new LabelsImported(
            '0e9fcb97-dd06-45e1-b32e-ff18967f3836',
            new Labels(
                new Label(new LabelName('foo'), true),
                new Label(new LabelName('bar'), false)
            )
        );

        $this->labelsImportedAsArray = [
            'item_id' => '0e9fcb97-dd06-45e1-b32e-ff18967f3836',
            'labels' => [
                [
                    'label' => 'foo',
                    'visibility' => true,
                ],
                [
                    'label' => 'bar',
                    'visibility' => false,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            '0e9fcb97-dd06-45e1-b32e-ff18967f3836',
            $this->labelsImported->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_labels_collection()
    {
        $this->assertEquals(
            new Labels(
                new Label(new LabelName('foo'), true),
                new Label(new LabelName('bar'), false)
            ),
            $this->labelsImported->getLabels()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $this->assertEquals(
            $this->labelsImported,
            LabelsImported::deserialize(
                $this->labelsImportedAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $this->assertEquals(
            $this->labelsImportedAsArray,
            $this->labelsImported->serialize()
        );
    }
}
