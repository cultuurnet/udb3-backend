<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class OfferLabelRelationTest extends TestCase
{
    private LabelName $labelName;

    private RelationType $relationType;

    private StringLiteral $offerId;

    private bool $imported;

    private LabelRelation $offerLabelRelation;

    protected function setUp(): void
    {
        $this->labelName = new LabelName('2dotstwice');
        $this->relationType = RelationType::place();
        $this->offerId = new StringLiteral('relationId');
        $this->imported = true;

        $this->offerLabelRelation = new LabelRelation(
            $this->labelName,
            $this->relationType,
            $this->offerId,
            true
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->labelName, $this->offerLabelRelation->getLabelName());
    }

    /**
     * @test
     */
    public function it_stores_a_relation_type(): void
    {
        $this->assertEquals(
            $this->relationType,
            $this->offerLabelRelation->getRelationType()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_relation_id(): void
    {
        $this->assertEquals($this->offerId, $this->offerLabelRelation->getRelationId());
    }

    /**
     * @test
     */
    public function it_can_encode_to_json(): void
    {
        $json = Json::encode($this->offerLabelRelation);

        $imported = $this->imported ? 'true' : 'false';
        $expectedJson = '{"labelName":"' . $this->labelName->toNative()
            . '","relationType":"' . $this->relationType->toString()
            . '","relationId":"' . $this->offerId->toNative()
            . '","imported":' . $imported . '}';

        $this->assertEquals($expectedJson, $json);
    }
}
