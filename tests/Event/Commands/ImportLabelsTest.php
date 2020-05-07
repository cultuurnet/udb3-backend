<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class ImportLabelsTest extends TestCase
{
    /**
     * @var string
     */
    private $itemId;

    /**
     * @var Labels
     */
    private $labels;

    /**
     * @var ImportLabels
     */
    private $importLabels;

    protected function setUp()
    {
        $this->itemId = '77a4a93b-f62d-4fed-b5c1-500064bcf2cf';

        $this->labels = new Labels(
            new Label(new LabelName('foo'), true),
            new Label(new LabelName('bar'), false)
        );

        $this->importLabels = new ImportLabels(
            $this->itemId,
            $this->labels
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_id()
    {
        $this->assertEquals(
            $this->itemId,
            $this->importLabels->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_labels_collection()
    {
        $this->assertEquals(
            $this->labels,
            $this->importLabels->getLabelsToImport()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_a_labels_collection_to_label_names()
    {
        $this->assertEquals(
            [
                new StringLiteral('foo'),
                new StringLiteral('bar'),
            ],
            $this->importLabels->getNames()
        );
    }

    /**
     * @test
     */
    public function it_has_permission_aanbod_bewerken()
    {
        $this->assertEquals(
            Permission::AANBOD_BEWERKEN(),
            $this->importLabels->getPermission()
        );
    }
}
