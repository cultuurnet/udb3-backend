<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

class AddLabelToQueryTest extends TestCase
{
    /**
     * @var AddLabelToQuery
     */
    protected $labelQuery;

    public function setUp()
    {
        $this->labelQuery = new AddLabelToQuery(
            'query',
            new Label(new LabelName('testlabel'))
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values()
    {
        $expectedQuery = 'query';
        $expectedLabel = new Label(new LabelName('testlabel'));

        $this->assertEquals($expectedQuery, $this->labelQuery->getQuery());
        $this->assertEquals($expectedLabel, $this->labelQuery->getLabel());
    }
}
