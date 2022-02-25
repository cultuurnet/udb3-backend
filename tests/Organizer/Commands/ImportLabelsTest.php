<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class ImportLabelsTest extends TestCase
{
    private string $organizerId;

    private Labels $labels;

    private ImportLabels $importLabels;

    protected function setUp(): void
    {
        $this->organizerId = '77a4a93b-f62d-4fed-b5c1-500064bcf2cf';

        $this->labels = new Labels(
            new Label(new LabelName('foo'), true),
            new Label(new LabelName('bar'), false)
        );

        $this->importLabels = new ImportLabels(
            $this->organizerId,
            $this->labels
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->importLabels->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_labels_collection(): void
    {
        $this->assertEquals(
            $this->labels,
            $this->importLabels->getLabels()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_a_labels_collection_to_label_names(): void
    {
        $this->assertEquals(
            [
                new StringLiteral('foo'),
                new StringLiteral('bar'),
            ],
            $this->importLabels->getLabelNames()
        );
    }

    /**
     * @test
     */
    public function it_has_permission_organisaties_bewerken(): void
    {
        $this->assertEquals(
            Permission::organisatiesBewerken(),
            $this->importLabels->getPermission()
        );
    }
}
