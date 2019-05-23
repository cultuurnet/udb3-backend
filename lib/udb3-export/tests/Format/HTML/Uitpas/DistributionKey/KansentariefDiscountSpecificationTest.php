<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey;
use CultureFeed_Uitpas_DistributionKey_Condition as Condition;

class KansentariefDiscountSpecificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var KansentariefDiscountSpecification
     */
    protected $specification;

    /**
     * @var DistributionKeyConditionFactory
     */
    protected $conditionFactory;

    /**
     * @var DistributionKeyFactory
     */
    protected $keyFactory;

    /**
     * @var array
     */
    protected $cardSystems;

    public function setUp()
    {
        $this->specification = new KansentariefDiscountSpecification();
    }

    /**
     * @test
     * @dataProvider satisfyingDistributionKeyProvider
     * @param CultureFeed_Uitpas_DistributionKey $key
     */
    public function it_is_satisfied_by_a_key_with_kansarm_condition(CultureFeed_Uitpas_DistributionKey $key)
    {
        $this->assertTrue($this->specification->isSatisfiedBy($key));
    }

    public function satisfyingDistributionKeyProvider()
    {
        $data = [];

        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        $cardSystems = [
            Condition::VALUE_MY_CARDSYSTEM,
            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM,
        ];

        foreach ($cardSystems as $cardSystem) {
            $data += [
                [
                    $keyFactory->buildKey(
                        '1.0',
                        [
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_KANSARM,
                                Condition::OPERATOR_IN,
                                $cardSystem
                            ),
                        ]
                    )
                ],
                [
                    $keyFactory->buildKey(
                        '0.0',
                        [
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_KANSARM,
                                Condition::OPERATOR_IN,
                                $cardSystem
                            ),
                        ]
                    )
                ],
                [
                    $keyFactory->buildKey(
                        '0.0',
                        [
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_KANSARM,
                                Condition::OPERATOR_IN,
                                $cardSystem
                            ),
                            $conditionFactory->buildCondition(
                                Condition::DEFINITION_PRICE,
                                Condition::OPERATOR_LESS_THAN,
                                '7'
                            ),
                        ]
                    )
                ],
            ];
        }

        return $data;
    }

    /**
     * @test
     * @dataProvider unsatisfyingDistributionKeyProvider
     * @param CultureFeed_Uitpas_DistributionKey $key
     */
    public function it_is_unsatisfied_by_a_key_without_kansarm_condition(CultureFeed_Uitpas_DistributionKey $key)
    {
        $this->assertFalse($this->specification->isSatisfiedBy($key));
    }

    public function unsatisfyingDistributionKeyProvider()
    {
        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        return [
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_PRICE,
                            Condition::OPERATOR_LESS_THAN,
                            '7'
                        ),
                    ]
                )
            ],
            [
                $keyFactory->buildKey(
                    '0.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_PRICE,
                            Condition::OPERATOR_LESS_THAN,
                            '7'
                        ),
                    ]
                )
            ],
        ];
    }
}
